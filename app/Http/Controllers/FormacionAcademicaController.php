<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;

class FormacionAcademicaController extends Controller
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
            'institucion' => 'required|string|max:150',
            'nivel_estudio' => 'nullable|string',
            'carrera_especialidad' => 'required|string|max:150',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date',
            'actualmente_estudiante' => 'nullable|boolean',
            'descripcion' => 'nullable|string',
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

        $id = DB::table('Formacion_Academica')->insertGetId([
            'id_usuario' => $idUsuario,
            'institucion' => $data['institucion'],
            'nivel_estudio' => $data['nivel_estudio'] ?? null,
            'carrera_especialidad' => $data['carrera_especialidad'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] ?? null,
            'actualmente_estudiante' => filter_var($data['actualmente_estudiante'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            'descripcion' => $data['descripcion'] ?? null,
            'visibilidad' => 'publico',
            'archivo_evidencia' => $archivoBytes ? DB::raw("decode('".base64_encode($archivoBytes)."', 'base64')") : null,
            'nombre_archivo_evidencia' => $nombreArchivo,
            'mime_tipo_evidencia' => $mimeTipo,
        ], 'id_formacion');

        return response()->json([
            'message' => 'Formación académica guardada exitosamente.',
            'id' => $id
        ], 201);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        $formacion = DB::selectOne('SELECT id_formacion FROM "Formacion_Academica" WHERE id_formacion = ? AND id_usuario = ?', [$id, $idUsuario]);

        if (!$formacion) {
            return response()->json(['message' => 'Formación académica no encontrada.'], 404);
        }

        DB::delete('DELETE FROM "Formacion_Academica" WHERE id_formacion = ?', [$id]);

        return response()->json(['message' => 'Formación académica eliminada exitosamente.']);
    }
}
