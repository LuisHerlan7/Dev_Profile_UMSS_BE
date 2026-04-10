<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;

class ProyectoController extends Controller
{
    public function __construct(
        private readonly GeneradorUsuarioSync $generadorUsuarioSync
    ) {}

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'desarrollador') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);
        $portafolio = DB::selectOne('SELECT id_portafolio FROM "Portafolio" WHERE id_usuario = ?', [$idUsuario]);

        // Asegurarse de que el portafolio exista
        if (!$portafolio) {
            $idPortafolio = DB::table('Portafolio')->insertGetId([
                'id_usuario' => $idUsuario,
                'titulo_portafolio' => 'Portafolio de ' . $user->name,
                'url_publica' => 'portafolio-' . $idUsuario . '-' . time(),
            ], 'id_portafolio');
        } else {
            $idPortafolio = $portafolio->id_portafolio;
        }

        $data = $request->validate([
            'nombre_proyecto' => 'required|string|max:150',
            'descripcion_proyecto' => 'required|string',
            'rol_desarrollador' => 'nullable|string|max:100',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'enlace_repositorio' => 'nullable|url|max:255',
            'enlace_proyecto_activo' => 'nullable|url|max:255',
            'estado_proyecto' => 'nullable|in:en_desarrollo,completado,pausado',
            'archivo' => 'nullable|file|max:50000', // max 50MB for projects maybe?
        ]);

        return DB::transaction(function () use ($data, $idPortafolio, $idUsuario, $request) {
            $idProyecto = DB::table('Proyecto')->insertGetId([
                'id_portafolio' => $idPortafolio,
                'nombre_proyecto' => $data['nombre_proyecto'],
                'descripcion_proyecto' => $data['descripcion_proyecto'],
                'rol_desarrollador' => $data['rol_desarrollador'] ?? null,
                'fecha_inicio' => $data['fecha_inicio'] ?? null,
                'fecha_fin' => $data['fecha_fin'] ?? null,
                'enlace_repositorio' => $data['enlace_repositorio'] ?? null,
                'enlace_proyecto_activo' => $data['enlace_proyecto_activo'] ?? null,
                'estado_proyecto' => $data['estado_proyecto'] ?? 'completado',
                'visibilidad' => 'publico',
            ], 'id_proyecto');

            if ($request->hasFile('archivo')) {
                $file = $request->file('archivo');
                $archivoBytes = file_get_contents($file->getRealPath());
                $nombreArchivo = $file->getClientOriginalName();
                $mimeTipo = $file->getClientMimeType();

                DB::table('Evidencia_Digital')->insert([
                    'id_proyecto' => $idProyecto,
                    'id_usuario' => $idUsuario,
                    'tipo_evidencia' => 'documento',
                    'titulo' => 'Evidencia de ' . $data['nombre_proyecto'],
                    'archivo' => DB::raw("decode('".base64_encode($archivoBytes)."', 'base64')"),
                    'nombre_archivo' => $nombreArchivo,
                    'tipo_mime' => $mimeTipo,
                    'tamaño_archivo' => $file->getSize(),
                ]);
            }

            return response()->json([
                'message' => 'Proyecto creado exitosamente.',
                'id' => $idProyecto
            ], 201);
        });
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        // Verify project ownership via portafolio
        $proyecto = DB::selectOne('
            SELECT p.id_proyecto 
            FROM "Proyecto" p
            JOIN "Portafolio" pf ON p.id_portafolio = pf.id_portafolio
            WHERE p.id_proyecto = ? AND pf.id_usuario = ?', 
        [$id, $idUsuario]);

        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        DB::delete('DELETE FROM "Proyecto" WHERE id_proyecto = ?', [$id]);

        return response()->json(['message' => 'Proyecto eliminado exitosamente.']);
    }
}
