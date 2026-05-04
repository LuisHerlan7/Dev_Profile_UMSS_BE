<?php

namespace App\Http\Controllers;

use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExperienciaLaboralController extends Controller
{
    public function __construct(
        private readonly GeneradorUsuarioSync $generadorUsuarioSync
    ) {}

    public function store(Request $request): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);
        $data = $this->validatePayload($request);

        $inserted = DB::selectOne(
            'INSERT INTO "Experiencia_Laboral" (
                titulo_puesto,
                id_usuario,
                nombre_empresa,
                descripcion_puesto,
                fecha_inicio,
                fecha_fin,
                es_trabajo_actual,
                visibilidad,
                archivo_evidencia,
                nombre_archivo_evidencia,
                mime_tipo_evidencia
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING id_experiencia',
            [
                $data['titulo_puesto'],
                $idUsuario,
                $data['nombre_empresa'],
                $data['descripcion_puesto'] ?? null,
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null,
                (bool) ($data['es_trabajo_actual'] ?? false),
                $data['visibilidad'] ?? 'publico',
                $this->readFileContent($request, 'archivo'),
                $request->file('archivo')?->getClientOriginalName(),
                $request->file('archivo')?->getClientMimeType(),
            ]
        );

        return response()->json([
            'message' => 'Experiencia guardada correctamente.',
            'id' => $inserted->id_experiencia,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);

        $exists = DB::selectOne(
            'SELECT id_experiencia FROM "Experiencia_Laboral" WHERE id_experiencia = ? AND id_usuario = ?',
            [$id, $idUsuario]
        );

        if (! $exists) {
            return response()->json(['message' => 'Experiencia no encontrada.'], 404);
        }

        $data = $this->validatePayload($request);
        $file = $request->file('archivo');

        DB::update(
            'UPDATE "Experiencia_Laboral"
             SET titulo_puesto = ?,
                 nombre_empresa = ?,
                 descripcion_puesto = ?,
                 fecha_inicio = ?,
                 fecha_fin = ?,
                 es_trabajo_actual = ?,
                 visibilidad = ?,
                 archivo_evidencia = COALESCE(?, archivo_evidencia),
                 nombre_archivo_evidencia = COALESCE(?, nombre_archivo_evidencia),
                 mime_tipo_evidencia = COALESCE(?, mime_tipo_evidencia)
             WHERE id_experiencia = ? AND id_usuario = ?',
            [
                $data['titulo_puesto'],
                $data['nombre_empresa'],
                $data['descripcion_puesto'] ?? null,
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null,
                (bool) ($data['es_trabajo_actual'] ?? false),
                $data['visibilidad'] ?? 'publico',
                $file ? file_get_contents($file->getRealPath()) : null,
                $file?->getClientOriginalName(),
                $file?->getClientMimeType(),
                $id,
                $idUsuario,
            ]
        );

        return response()->json([
            'message' => 'Experiencia actualizada correctamente.',
            'id' => $id,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);
        DB::delete(
            'DELETE FROM "Experiencia_Laboral" WHERE id_experiencia = ? AND id_usuario = ?',
            [$id, $idUsuario]
        );

        return response()->json([
            'message' => 'Experiencia eliminada correctamente.',
        ]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'titulo_puesto' => ['required', 'string', 'max:150'],
            'nombre_empresa' => ['required', 'string', 'max:150'],
            'descripcion_puesto' => ['nullable', 'string', 'max:3000'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'es_trabajo_actual' => ['nullable', 'boolean'],
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

        return $file ? file_get_contents($file->getRealPath()) : null;
    }
}
