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
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, decode(?, \'base64\'), ?, ?)
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
        ], 201, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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
                 archivo_evidencia = COALESCE(decode(?, \'base64\'), archivo_evidencia),
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
                $file ? base64_encode(file_get_contents($file->getRealPath())) : null,
                $file?->getClientOriginalName(),
                $file?->getClientMimeType(),
                $id,
                $idUsuario,
            ]
        );

        return response()->json([
            'message' => 'Experiencia actualizada correctamente.',
            'id' => $id,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'titulo_puesto' => ['required', 'string', 'min:3', 'max:150', 'regex:/^(?=.*\pL)[\pL\pN\s.,&()\'\/-]+$/u'],
            'nombre_empresa' => ['required', 'string', 'min:2', 'max:150', 'regex:/^(?=.*\pL)[\pL\pN\s.,&()\'\/-]+$/u'],
            'descripcion_puesto' => ['nullable', 'string', 'min:20', 'max:3000', 'regex:/^(?=.*\pL).+$/su'],
            'fecha_inicio' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:today'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio', 'before_or_equal:today'],
            'es_trabajo_actual' => ['nullable', 'boolean'],
            'visibilidad' => ['nullable', 'in:publico,privado'],
            'archivo' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:5120'],
        ], [
            'titulo_puesto.min' => 'El cargo debe tener al menos 3 caracteres.',
            'titulo_puesto.regex' => 'El cargo debe contener letras y no puede estar formado solo por simbolos o numeros.',
            'nombre_empresa.min' => 'El nombre de la empresa debe tener al menos 2 caracteres.',
            'nombre_empresa.regex' => 'La empresa debe contener letras y no puede estar formada solo por simbolos o numeros.',
            'descripcion_puesto.min' => 'La descripcion debe tener al menos 20 caracteres.',
            'descripcion_puesto.regex' => 'La descripcion debe contener texto valido.',
            'fecha_inicio.after_or_equal' => 'La fecha de inicio no puede ser anterior a 1950.',
            'fecha_inicio.before_or_equal' => 'La fecha de inicio no puede estar en el futuro.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'fecha_fin.before_or_equal' => 'La fecha de fin no puede estar en el futuro.',
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
