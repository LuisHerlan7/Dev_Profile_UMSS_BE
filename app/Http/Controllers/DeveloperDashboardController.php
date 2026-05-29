<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeveloperDashboardController extends Controller
{
    public function __construct(
        private readonly GeneradorUsuarioSync $generadorUsuarioSync
    ) {}

    public function index(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('RESTORED_DASHBOARD_CONTROLLER_CALLED');
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== 'desarrollador') {
            return response()->json([
                'message' => 'Solo desarrolladores pueden acceder a este panel.',
            ], 403);
        }

        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        $usuario = DB::selectOne(
            'SELECT id_usuario, nombre_completo, correo, correo_contacto, telefono, profesion, biografia, visibilidad_perfil, fecha_creacion, fecha_actualizacion,
                    titulos_jerarquia_json, roles_jerarquia_json,
                    CASE WHEN fotografia IS NOT NULL THEN true ELSE false END AS tiene_fotografia
             FROM "Usuario" WHERE id_usuario = ?',
            [$idUsuario]
        );

        $portafolio = DB::selectOne(
            'SELECT * FROM "Portafolio" WHERE id_usuario = ?',
            [$idUsuario]
        );

        $proyectos = [];
        if ($portafolio) {
            $proyectos = DB::select(
                'SELECT p.*, COALESCE(
                    (SELECT json_agg(t.nombre_tecnologia ORDER BY t.nombre_tecnologia)
                     FROM "Tecnologia_Proyecto" tp
                     INNER JOIN "Tecnologia" t ON t.id_tecnologia = tp.id_tecnologia
                     WHERE tp.id_proyecto = p.id_proyecto),
                    \'[]\'::json
                ) AS tecnologias
                 FROM "Proyecto" p
                 WHERE p.id_portafolio = ?
                 ORDER BY p.fecha_creacion DESC NULLS LAST, p.id_proyecto DESC',
                [$portafolio->id_portafolio]
            );
        }

        $habilidades = DB::select(
            'SELECT h.*,
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
             WHERE h.id_usuario = ?
             ORDER BY h.id_habilidad',
            [$idUsuario]
        );

        $experiencias = DB::select(
            'SELECT id_experiencia, titulo_puesto, id_usuario, nombre_empresa, descripcion_puesto, fecha_inicio, fecha_fin, es_trabajo_actual, visibilidad, nombre_archivo_evidencia, mime_tipo_evidencia FROM "Experiencia_Laboral" WHERE id_usuario = ? ORDER BY fecha_inicio DESC NULLS LAST',
            [$idUsuario]
        );

        $formaciones = DB::select(
            'SELECT id_formacion, id_usuario, institucion, nivel_estudio, carrera_especialidad, fecha_inicio, fecha_fin, actualmente_estudiante, descripcion, visibilidad, nombre_archivo_evidencia, mime_tipo_evidencia FROM "Formacion_Academica" WHERE id_usuario = ? ORDER BY fecha_inicio DESC NULLS LAST',
            [$idUsuario]
        );

        foreach ($experiencias as $exp) {
            if (!empty($exp->nombre_archivo_evidencia)) {
                $exp->evidenceUrl = '/api/developer/files/experiencia/' . $exp->id_experiencia;
                $exp->fileSize = $exp->nombre_archivo_evidencia;
            }
        }

        foreach ($formaciones as $form) {
            if (!empty($form->nombre_archivo_evidencia)) {
                $form->evidenceUrl = '/api/developer/files/formacion/' . $form->id_formacion;
                $form->fileSize = $form->nombre_archivo_evidencia;
            }
        }

        $visibilidad = DB::selectOne(
            'SELECT * FROM "Configuracion_Visibilidad" WHERE id_usuario = ?',
            [$idUsuario]
        );

        $redes = DB::select(
            'SELECT * FROM "Red_Profesional" WHERE id_usuario = ? ORDER BY fecha_agregado DESC NULLS LAST',
            [$idUsuario]
        );

        $evidencias = DB::select(
            'SELECT e.*, p.nombre_proyecto
             FROM "Evidencia_Digital" e
             LEFT JOIN "Proyecto" p ON e.id_proyecto = p.id_proyecto
             WHERE e.id_usuario = ?
             ORDER BY e.fecha_carga DESC',
            [$idUsuario]
        );

        $evidenciasMapeadas = array_map(function ($e) {
            return [
                'id' => (string) $e->id_evidencia,
                'title' => $e->titulo,
                'type' => $e->tipo_evidencia,
                'status' => $e->estado_revision,
                'file_url' => '/api/developer/files/evidencia/' . $e->id_evidencia,
                'project' => $e->nombre_proyecto ?? 'Proyecto Sin Nombre',
                'project_id' => $e->id_proyecto ? (string) $e->id_proyecto : null,
                'created_at' => $e->fecha_carga ? \Illuminate\Support\Carbon::parse($e->fecha_carga)->format('d/m/Y H:i') : null,
            ];
        }, $evidencias);

        $proyectosNormalizados = array_map(function ($p) use ($idUsuario) {
            $tags = $p->tecnologias ?? null;
            if (is_string($tags)) {
                $decoded = json_decode($tags, true);
                $tags = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($tags)) {
                $tags = [];
            }
            $p->tecnologias = $tags;

            // Check if project has an evidence row
            $evidenceCheck = DB::selectOne('SELECT id_evidencia, nombre_archivo FROM "Evidencia_Digital" WHERE id_proyecto = ? LIMIT 1', [$p->id_proyecto]);
            if ($evidenceCheck) {
                $p->evidenceUrl = '/api/developer/files/proyecto/' . $p->id_proyecto;
                $p->fileSize = $evidenceCheck->nombre_archivo ?? 'Documento adjunto'; // Or format properly
            }

            return $p;
        }, $proyectos);

        if ($usuario) {
            if ($usuario->tiene_fotografia ?? false) {
                $ts = $usuario->fecha_actualizacion ? strtotime($usuario->fecha_actualizacion) : time();
                $usuario->fotografiaUrl = '/api/developer/files/avatar/' . $usuario->id_usuario . '?t=' . $ts;
            }
        }

        return response()->json([
            'auth_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'preferred_language' => $user->preferred_language ?: 'es',
            ],
            'usuario' => $usuario,
            'portafolio' => $portafolio,
            'proyectos' => $proyectosNormalizados,
            'habilidades' => $habilidades,
            'experiencias' => $experiencias,
            'formaciones' => $formaciones,
            'visibilidad' => $visibilidad,
            'redes' => $redes,
            'evidences' => $evidenciasMapeadas,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
