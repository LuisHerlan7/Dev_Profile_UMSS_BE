<?php

namespace App\Http\Controllers;

use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormacionAcademicaController extends Controller
{
    public function __construct(
        private readonly GeneradorUsuarioSync $generadorUsuarioSync
    ) {}

    public function store(Request $request): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);
        $data = $this->validatePayload($request);

        $inserted = DB::selectOne(
            'INSERT INTO "Formacion_Academica" (
                id_usuario,
                institucion,
                nivel_estudio,
                carrera_especialidad,
                fecha_inicio,
                fecha_fin,
                actualmente_estudiante,
                descripcion,
                visibilidad,
                archivo_evidencia,
                nombre_archivo_evidencia,
                mime_tipo_evidencia
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, decode(?, \'base64\'), ?, ?)
            RETURNING id_formacion',
            [
                $idUsuario,
                $data['institucion'],
                $data['nivel_estudio'] ?? 'certificado',
                $data['carrera_especialidad'],
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null,
                (bool) ($data['actualmente_estudiante'] ?? false),
                $data['descripcion'] ?? null,
                $data['visibilidad'] ?? 'publico',
                $this->readFileContent($request, 'archivo'),
                $request->file('archivo')?->getClientOriginalName(),
                $request->file('archivo')?->getClientMimeType(),
            ]
        );

        return response()->json([
            'message' => 'Formación guardada correctamente.',
            'id' => $inserted->id_formacion,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);

        $exists = DB::selectOne(
            'SELECT id_formacion FROM "Formacion_Academica" WHERE id_formacion = ? AND id_usuario = ?',
            [$id, $idUsuario]
        );

        if (! $exists) {
            return response()->json(['message' => 'Formación no encontrada.'], 404);
        }

        $data = $this->validatePayload($request);
        $file = $request->file('archivo');

        DB::update(
            'UPDATE "Formacion_Academica"
             SET institucion = ?,
                 nivel_estudio = ?,
                 carrera_especialidad = ?,
                 fecha_inicio = ?,
                 fecha_fin = ?,
                 actualmente_estudiante = ?,
                 descripcion = ?,
                 visibilidad = ?,
                 archivo_evidencia = COALESCE(decode(?, \'base64\'), archivo_evidencia),
                 nombre_archivo_evidencia = COALESCE(?, nombre_archivo_evidencia),
                 mime_tipo_evidencia = COALESCE(?, mime_tipo_evidencia)
             WHERE id_formacion = ? AND id_usuario = ?',
            [
                $data['institucion'],
                $data['nivel_estudio'] ?? 'certificado',
                $data['carrera_especialidad'],
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null,
                (bool) ($data['actualmente_estudiante'] ?? false),
                $data['descripcion'] ?? null,
                $data['visibilidad'] ?? 'publico',
                $file ? base64_encode(file_get_contents($file->getRealPath())) : null,
                $file?->getClientOriginalName(),
                $file?->getClientMimeType(),
                $id,
                $idUsuario,
            ]
        );

        return response()->json([
            'message' => 'Formación actualizada correctamente.',
            'id' => $id,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);
        DB::delete(
            'DELETE FROM "Formacion_Academica" WHERE id_formacion = ? AND id_usuario = ?',
            [$id, $idUsuario]
        );

        return response()->json([
            'message' => 'Formación eliminada correctamente.',
        ]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'institucion' => ['required', 'string', 'max:150', 'regex:/^(?=.*\pL)[\pL\pN\s.,&()\'\/-]+$/u'],
            'nivel_estudio' => ['nullable', 'in:secundaria,diploma,licenciatura,maestria,doctorado,curso,certificado'],
            'carrera_especialidad' => ['required', 'string', 'max:150', 'regex:/^(?=.*\pL)[\pL\pN\s.,&()\'\/-]+$/u'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'actualmente_estudiante' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string', 'max:3000', 'regex:/^(?=.*\pL)[\pL\pN\s.,:;()\/%\-]+$/u'],
            'visibilidad' => ['nullable', 'in:publico,privado'],
            'archivo' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:5120'],
        ]);
    }

    private function resolveDeveloperId(Request $request): int
    {
        $user = $request->user();
        if ($user->role !== 'desarrollador') {
            abort(403, 'No autorizado');
        }

        return $this->generadorUsuarioSync->ensureForLaravelUser($user);
    }

    private function readFileContent(Request $request, string $key): ?string
    {
        $file = $request->file($key);

        return $file ? base64_encode(file_get_contents($file->getRealPath())) : null;
    }
}
