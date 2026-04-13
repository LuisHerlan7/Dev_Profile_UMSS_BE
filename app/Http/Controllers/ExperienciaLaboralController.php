<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;

class ExperienciaLaboralController extends Controller
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

        $data = $request->validate([
            'titulo_puesto' => 'required|string|max:150',
            'nombre_empresa' => 'required|string|max:150',
            'descripcion_puesto' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date',
            'es_trabajo_actual' => 'nullable|boolean',
            'ubicacion' => 'nullable|string|max:150',
            'tipo_contrato' => 'nullable|string',
            'archivo' => 'nullable|file|max:5120', // max 5MB
        ]);

        $archivoBytes = null;
        $nombreArchivo = null;
        $mimeTipo = null;

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $archivoBytes = file_get_contents($file->getRealPath());
            $nombreArchivo = $file->getClientOriginalName();
            $mimeTipo = $file->getClientMimeType();
        }

        $id = DB::table('Experiencia_Laboral')->insertGetId([
            'id_usuario' => $idUsuario,
            'titulo_puesto' => $data['titulo_puesto'],
            'nombre_empresa' => $data['nombre_empresa'],
            'descripcion_puesto' => $data['descripcion_puesto'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] ?? null,
            'es_trabajo_actual' => filter_var($data['es_trabajo_actual'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            'ubicacion' => $data['ubicacion'] ?? null,
            'tipo_contrato' => $data['tipo_contrato'] ?? null,
            'visibilidad' => 'publico',
            'archivo_evidencia' => $archivoBytes ? DB::raw("decode('".base64_encode($archivoBytes)."', 'base64')") : null,
            'nombre_archivo_evidencia' => $nombreArchivo,
            'mime_tipo_evidencia' => $mimeTipo,
        ], 'id_experiencia');

        return response()->json([
            'message' => 'Experiencia guardada exitosamente.',
            'id' => $id
        ], 201);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        $experiencia = DB::selectOne('SELECT id_experiencia FROM "Experiencia_Laboral" WHERE id_experiencia = ? AND id_usuario = ?', [$id, $idUsuario]);

        if (!$experiencia) {
            return response()->json(['message' => 'Experiencia no encontrada.'], 404);
        }

        DB::delete('DELETE FROM "Experiencia_Laboral" WHERE id_experiencia = ?', [$id]);

        return response()->json(['message' => 'Experiencia eliminada exitosamente.']);
    }
}
