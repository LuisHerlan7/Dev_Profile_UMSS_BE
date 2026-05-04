<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicProfileController extends Controller
{
    public function index(Request $request)
    {
        // Obtener usuarios públicos con rol desarrollador
        $usuarios = DB::select('
            SELECT u.id_usuario,
                   u.nombre_completo,
                   u.profesion,
                   u.fecha_actualizacion,
                   COALESCE(cv.mostrar_habilidades, TRUE) AS mostrar_habilidades
            FROM "Usuario" u
            INNER JOIN users ur ON ur.email = u.correo
            LEFT JOIN "Configuracion_Visibilidad" cv ON cv.id_usuario = u.id_usuario
            WHERE u.visibilidad_perfil IN (\'publico\', \'personalizado\')
              AND COALESCE(cv.mostrar_informacion_general, TRUE) = TRUE
              AND ur.role = \'desarrollador\'
            ORDER BY u.id_usuario DESC
        ');

        $result = [];
        foreach ($usuarios as $u) {
            $habilidades = ($u->mostrar_habilidades ?? true)
                ? DB::select('
                    SELECT nombre_habilidad
                    FROM "Habilidad"
                    WHERE id_usuario = ? AND tipo_habilidad = \'tecnica\'
                    LIMIT 4
                ', [$u->id_usuario])
                : [];

            $ts = $u->fecha_actualizacion ? strtotime($u->fecha_actualizacion) : time();
            $avatarUrl = "/api/developer/files/avatar/{$u->id_usuario}?t={$ts}";

            $result[] = [
                'id' => $u->id_usuario,
                'name' => $u->nombre_completo,
                'title' => $u->profesion ?? 'Desarrollador',
                'level' => 'Junior',
                'type' => 'Full Stack',
                'tags' => array_map(fn($h) => $h->nombre_habilidad, $habilidades),
                'avatarUrl' => $avatarUrl
            ];
        }
        return response()->json($result);
    }

    public function show($id)
    {
        // 1. Datos base del usuario
        $u = DB::selectOne('
            SELECT u.*, ur.email, ur.role
            FROM "Usuario" u
            INNER JOIN users ur ON ur.email = u.correo
            WHERE u.id_usuario = ?
              AND u.visibilidad_perfil IN (\'publico\', \'personalizado\')
              AND ur.role = \'desarrollador\'
        ', [$id]);

        if (!$u) {
            return response()->json(['message' => 'Perfil no encontrado o privado'], 404);
        }

        // 2. Configuración de visibilidad
        $config = DB::selectOne('SELECT * FROM "Configuracion_Visibilidad" WHERE id_usuario = ?', [$id]);
        if ($config && $u->visibilidad_perfil === 'personalizado' && !($config->mostrar_informacion_general ?? true)) {
            return response()->json(['message' => 'Perfil no encontrado o privado'], 404);
        }
        
        // 3. Redes sociales
        $redes = DB::select('SELECT nombre_red, enlace_perfil FROM "Red_Profesional" WHERE id_usuario = ?', [$id]);

        // 4. Procesar Destacados (Highlights)
        $highlights = ['projects' => [], 'skills' => [], 'trajectory' => []];
        if (!empty($u->highlights_json)) {
            $decoded = json_decode($u->highlights_json, true);
            if (is_array($decoded)) {
                $highlights = array_merge($highlights, $decoded);
            }
        }

        // 5. Habilidades (Solo si está permitido)
        $habilidades = [];
        if (!$config || $config->mostrar_habilidades) {
            $habilidades = DB::select('
                SELECT h.id_habilidad,
                       h.nombre_habilidad,
                       h.tipo_habilidad,
                       h.nivel_dominio,
                       h.porcentaje_dominio,
                       COALESCE(v.vinculos, \'[]\'::json) AS vinculos
                FROM "Habilidad" h
                LEFT JOIN (
                    SELECT hv.id_habilidad,
                           json_agg(
                               json_build_object(
                                   \'id\', hv.id_vinculo,
                                   \'tipo_referencia\', hv.tipo_referencia,
                                   \'etiqueta_referencia\', hv.etiqueta_referencia,
                                   \'referencia_id\', COALESCE(hv.id_proyecto, hv.id_experiencia, hv.id_formacion)
                               )
                               ORDER BY hv.id_vinculo
                           ) AS vinculos
                    FROM "Habilidad_Vinculo" hv
                    GROUP BY hv.id_habilidad
                ) v ON v.id_habilidad = h.id_habilidad
                WHERE h.id_usuario = ?', [$id]);
            // Filtrar por destacados si hay alguno seleccionado
            if (!empty($highlights['skills'])) {
                $habilidades = array_values(array_filter($habilidades, fn($s) =>
                    $this->matchesHighlight(
                        $highlights['skills'],
                        [(string) $s->id_habilidad],
                        [$s->nombre_habilidad]
                    )
                ));
            }
        }

        // 6. Proyectos (Solo si está permitido)
        $proyectosResult = [];
        if (!$config || $config->mostrar_proyectos) {
            // Necesitamos el id_portafolio
            $portafolio = DB::selectOne('SELECT id_portafolio FROM "Portafolio" WHERE id_usuario = ?', [$id]);
            if ($portafolio) {
                $proyectos = DB::select('
                    SELECT p.*, COALESCE(
                        (SELECT json_agg(t.nombre_tecnologia ORDER BY t.nombre_tecnologia)
                         FROM "Tecnologia_Proyecto" tp
                         INNER JOIN "Tecnologia" t ON t.id_tecnologia = tp.id_tecnologia
                         WHERE tp.id_proyecto = p.id_proyecto),
                        \'[]\'::json
                    ) AS tecnologias
                    FROM "Proyecto" p
                    WHERE p.id_portafolio = ? AND p.visibilidad = \'publico\'
                ', [$portafolio->id_portafolio]);

                // Filtrar por destacados
                if (!empty($highlights['projects'])) {
                    $proyectos = array_values(array_filter($proyectos, fn($p) =>
                        $this->matchesHighlight(
                            $highlights['projects'],
                            [(string) $p->id_proyecto],
                            [$p->nombre_proyecto]
                        )
                    ));
                }

                foreach ($proyectos as $p) {
                    $tags = $p->tecnologias ?? '[]';
                    $proyectosResult[] = [
                        'id' => $p->id_proyecto,
                        'title' => $p->nombre_proyecto,
                        'subtitle' => $p->rol_desarrollador ?? 'Colaborador',
                        'description' => $p->descripcion_proyecto,
                        'tags' => is_string($tags) ? json_decode($tags, true) : $tags,
                        'liveUrl' => $p->enlace_proyecto_activo,
                        'repoUrl' => $p->enlace_repositorio
                    ];
                }
            }
        }

        // 7. Trayectoria (Experiencia + Formación)
        $timeline = [];
        if (!$config || $config->mostrar_experiencia) {
            $exps = DB::select('SELECT * FROM "Experiencia_Laboral" WHERE id_usuario = ? AND visibilidad = \'publico\'', [$id]);
            if (!empty($highlights['trajectory'])) {
                $exps = array_filter($exps, fn($e) =>
                    $this->matchesHighlight(
                        $highlights['trajectory'],
                        ["exp-{$e->id_experiencia}", (string) $e->id_experiencia],
                        [trim(sprintf('%s @ %s', $e->titulo_puesto, $e->nombre_empresa))]
                    )
                );
            }
            foreach ($exps as $e) {
                $start = date('M Y', strtotime($e->fecha_inicio));
                $end = $e->es_trabajo_actual ? 'Presente' : ($e->fecha_fin ? date('M Y', strtotime($e->fecha_fin)) : '?');
                $timeline[] = [
                    'id' => "exp-{$e->id_experiencia}",
                    'period' => "{$start} — {$end}",
                    'title' => $e->titulo_puesto,
                    'company' => $e->nombre_empresa,
                    'detail' => $e->descripcion_puesto,
                    'type' => 'experience',
                    'dateNum' => strtotime($e->fecha_inicio)
                ];
            }
        }

        if (!$config || $config->mostrar_formacion) {
            $forms = DB::select('SELECT * FROM "Formacion_Academica" WHERE id_usuario = ? AND visibilidad = \'publico\'', [$id]);
            if (!empty($highlights['trajectory'])) {
                $forms = array_filter($forms, fn($f) =>
                    $this->matchesHighlight(
                        $highlights['trajectory'],
                        ["form-{$f->id_formacion}", (string) $f->id_formacion],
                        [
                            $f->carrera_especialidad,
                            trim(sprintf('%s · %s', $f->carrera_especialidad, $f->institucion)),
                        ]
                    )
                );
            }
            foreach ($forms as $f) {
                $start = date('Y', strtotime($f->fecha_inicio));
                $end = $f->actualmente_estudiante ? 'Actualidad' : ($f->fecha_fin ? date('Y', strtotime($f->fecha_fin)) : '?');
                $timeline[] = [
                    'id' => "form-{$f->id_formacion}",
                    'period' => "{$start} — {$end}",
                    'title' => $f->nivel_estudio . ' en ' . $f->carrera_especialidad,
                    'company' => $f->institucion,
                    'detail' => $f->descripcion,
                    'type' => 'education',
                    'dateNum' => strtotime($f->fecha_inicio)
                ];
            }
        }

        // Ordenar trayectoria por fecha descendente
        usort($timeline, fn($a, $b) => $b['dateNum'] <=> $a['dateNum']);

        // Avatar URL
        $ts = $u->fecha_actualizacion ? strtotime($u->fecha_actualizacion) : time();
        $avatarUrl = "/api/developer/files/avatar/{$u->id_usuario}?t={$ts}";

        return response()->json([
            'profile' => [
                'id' => $u->id_usuario,
                'name' => $u->nombre_completo,
                'title' => $u->profesion ?? 'Desarrollador',
                'summary' => $u->biografia ?? 'Sin biografía disponible.',
                'avatarUrl' => $avatarUrl,
                'email' => (!$config || (($config->mostrar_contacto ?? true) && ($config->mostrar_correo ?? true)))
                    ? ($u->correo_contacto ?: $u->email)
                    : null,
                'phone' => (!$config || (($config->mostrar_contacto ?? true) && ($config->mostrar_telefono ?? false)))
                    ? $u->telefono
                    : null,
                'titleHierarchy' => $this->decodeJsonArray($u->titulos_jerarquia_json ?? null),
                'roleHierarchy' => $this->decodeJsonArray($u->roles_jerarquia_json ?? null),
            ],
            'social' => (!$config || ($config->mostrar_redes_sociales ?? true))
                ? $this->normalizeSocialLinks($redes)
                : new \stdClass(),
            'skills' => $habilidades,
            'projects' => $proyectosResult,
            'timeline' => $timeline,
            'config' => $config
        ]);
    }

    public function showProject($portfolioId, $projectId)
    {
        $owner = DB::selectOne('
            SELECT u.id_usuario,
                   u.nombre_completo,
                   u.profesion,
                   u.biografia,
                   u.fecha_actualizacion,
                   u.visibilidad_perfil,
                   cv.mostrar_informacion_general,
                   cv.mostrar_proyectos,
                   cv.mostrar_redes_sociales,
                   cv.mostrar_contacto,
                   cv.mostrar_correo,
                   cv.mostrar_telefono,
                   cv.mostrar_habilidades
            FROM "Portafolio" pf
            INNER JOIN "Usuario" u ON u.id_usuario = pf.id_usuario
            INNER JOIN users ur ON ur.email = u.correo
            LEFT JOIN "Configuracion_Visibilidad" cv ON cv.id_usuario = u.id_usuario
            WHERE pf.id_usuario = ?
              AND u.visibilidad_perfil IN (\'publico\', \'personalizado\')
              AND ur.role = \'desarrollador\'
        ', [$portfolioId]);

        if (!$owner) {
            return response()->json(['message' => 'Proyecto no encontrado o privado'], 404);
        }

        if ($owner->visibilidad_perfil === 'personalizado' && !($owner->mostrar_informacion_general ?? true)) {
            return response()->json(['message' => 'Proyecto no encontrado o privado'], 404);
        }

        if (!($owner->mostrar_proyectos ?? true)) {
            return response()->json(['message' => 'Proyecto no encontrado o privado'], 404);
        }

        $portafolio = DB::selectOne(
            'SELECT id_portafolio FROM "Portafolio" WHERE id_usuario = ?',
            [$owner->id_usuario]
        );

        if (!$portafolio) {
            return response()->json(['message' => 'Proyecto no encontrado o privado'], 404);
        }

        $project = DB::selectOne('
            SELECT p.*,
                   COALESCE(
                       (SELECT json_agg(t.nombre_tecnologia ORDER BY t.nombre_tecnologia)
                        FROM "Tecnologia_Proyecto" tp
                        INNER JOIN "Tecnologia" t ON t.id_tecnologia = tp.id_tecnologia
                        WHERE tp.id_proyecto = p.id_proyecto),
                       \'[]\'::json
                   ) AS tecnologias
            FROM "Proyecto" p
            WHERE p.id_proyecto = ?
              AND p.id_portafolio = ?
              AND p.visibilidad = \'publico\'
        ', [$projectId, $portafolio->id_portafolio]);

        if (!$project) {
            return response()->json(['message' => 'Proyecto no encontrado o privado'], 404);
        }

        $evidences = DB::select('
            SELECT id_evidencia AS id,
                   titulo,
                   url_enlace,
                   nombre_archivo,
                   tipo_mime,
                   fecha_carga
            FROM "Evidencia_Digital"
            WHERE id_proyecto = ?
              AND COALESCE(visibilidad, \'publico\') = \'publico\'
            ORDER BY fecha_carga DESC NULLS LAST, id_evidencia DESC
        ', [$projectId]);

        $redes = DB::select(
            'SELECT nombre_red, enlace_perfil FROM "Red_Profesional" WHERE id_usuario = ?',
            [$owner->id_usuario]
        );

        $ts = $owner->fecha_actualizacion ? strtotime($owner->fecha_actualizacion) : time();
        $avatarUrl = "/api/developer/files/avatar/{$owner->id_usuario}?t={$ts}";
        $tags = is_string($project->tecnologias) ? json_decode($project->tecnologias, true) : $project->tecnologias;

        return response()->json([
            'project' => [
                'id' => $project->id_proyecto,
                'portfolioId' => $owner->id_usuario,
                'title' => $project->nombre_proyecto,
                'subtitle' => $project->rol_desarrollador ?? 'Colaborador',
                'summary' => $project->descripcion_proyecto,
                'description' => $project->descripcion_tecnica,
                'status' => $project->estado_proyecto,
                'tags' => is_array($tags) ? array_values($tags) : [],
                'liveUrl' => $project->enlace_proyecto_activo,
                'repoUrl' => $project->enlace_repositorio,
                'startDate' => $project->fecha_inicio,
                'endDate' => $project->fecha_fin,
                'evidences' => $evidences,
            ],
            'owner' => [
                'id' => $owner->id_usuario,
                'name' => $owner->nombre_completo,
                'title' => $owner->profesion ?? 'Desarrollador',
                'summary' => $owner->biografia ?? 'Sin biografía disponible.',
                'avatarUrl' => $avatarUrl,
            ],
            'social' => ($owner->mostrar_redes_sociales ?? true)
                ? $this->normalizeSocialLinks($redes)
                : new \stdClass(),
            'contact' => [
                'emailVisible' => (bool) (($owner->mostrar_contacto ?? true) && ($owner->mostrar_correo ?? true)),
                'whatsappVisible' => (bool) (($owner->mostrar_contacto ?? true) && ($owner->mostrar_telefono ?? false)),
            ],
        ]);
    }

    private function decodeJsonArray(?string $value): array
    {
        if (! $value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }

    private function matchesHighlight(array $highlights, array $identifiers = [], array $labels = []): bool
    {
        $normalizedHighlights = array_map(
            fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8'),
            $highlights
        );

        foreach ($identifiers as $identifier) {
            if (in_array(mb_strtolower(trim((string) $identifier), 'UTF-8'), $normalizedHighlights, true)) {
                return true;
            }
        }

        foreach ($labels as $label) {
            if (in_array(mb_strtolower(trim((string) $label), 'UTF-8'), $normalizedHighlights, true)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSocialLinks(array $redes): array
    {
        $normalized = [];

        foreach ($redes as $red) {
            $name = mb_strtolower(trim((string) ($red->nombre_red ?? '')), 'UTF-8');
            $url = (string) ($red->enlace_perfil ?? '');

            if ($url === '') {
                continue;
            }

            $key = match (true) {
                str_contains($name, 'github'), str_contains($name, 'git') => 'github',
                str_contains($name, 'linkedin') => 'linkedin',
                str_contains($name, 'website'),
                str_contains($name, 'web'),
                str_contains($name, 'sitio'),
                str_contains($name, 'portfolio'),
                str_contains($name, 'portafolio') => 'website',
                default => $name,
            };

            $normalized[$key] = $url;
        }

        return $normalized;
    }
}
