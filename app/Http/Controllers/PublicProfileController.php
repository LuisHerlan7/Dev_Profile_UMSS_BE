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
            SELECT u.id_usuario, u.nombre_completo, u.profesion, u.fecha_actualizacion
            FROM "Usuario" u
            INNER JOIN users ur ON ur.email = u.correo
            WHERE u.visibilidad_perfil = \'publico\'
              AND ur.role = \'desarrollador\'
            ORDER BY u.id_usuario DESC
        ');

        $result = [];
        foreach ($usuarios as $u) {
            $habilidades = DB::select('
                SELECT nombre_habilidad 
                FROM "Habilidad" 
                WHERE id_usuario = ? AND tipo_habilidad = \'tecnica\'
                LIMIT 4
            ', [$u->id_usuario]);

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
            WHERE u.id_usuario = ? AND u.visibilidad_perfil = \'publico\' AND ur.role = \'desarrollador\'
        ', [$id]);

        if (!$u) {
            return response()->json(['message' => 'Perfil no encontrado o privado'], 404);
        }

        // 2. Configuración de visibilidad
        $config = DB::selectOne('SELECT * FROM "Configuracion_Visibilidad" WHERE id_usuario = ?', [$id]);
        
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
            $habilidades = DB::select('SELECT id_habilidad, nombre_habilidad, tipo_habilidad, nivel_dominio, porcentaje_dominio FROM "Habilidad" WHERE id_usuario = ?', [$id]);
            // Filtrar por destacados si hay alguno seleccionado
            if (!empty($highlights['skills'])) {
                $habilidades = array_values(array_filter($habilidades, fn($s) => in_array((string)$s->id_habilidad, $highlights['skills'])));
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
                    $proyectos = array_values(array_filter($proyectos, fn($p) => in_array((string)$p->id_proyecto, $highlights['projects'])));
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
                $exps = array_filter($exps, fn($e) => in_array("exp-{$e->id_experiencia}", $highlights['trajectory']));
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
                $forms = array_filter($forms, fn($f) => in_array("form-{$f->id_formacion}", $highlights['trajectory']));
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
                'email' => $u->email,
                'phone' => $u->telefono,
            ],
            'social' => array_column($redes, 'enlace_perfil', 'nombre_red'),
            'skills' => $habilidades,
            'projects' => $proyectosResult,
            'timeline' => $timeline,
            'config' => $config
        ]);
    }
}
