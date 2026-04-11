<?php

namespace App\Http\Controllers;

use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProyectoController extends Controller
{
    public function __construct(
        private readonly GeneradorUsuarioSync $generadorUsuarioSync
    ) {}

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'desarrollador') {
                return response()->json(['message' => 'No autorizado'], 403);
            }

            $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);
            $portafolio = DB::selectOne(
                'SELECT id_portafolio FROM "Portafolio" WHERE id_usuario = ?',
                [$idUsuario]
            );

            if (! $portafolio) {
                $insertedPortafolio = DB::selectOne(
                    'INSERT INTO "Portafolio" (id_usuario, titulo_portafolio, url_publica)
                     VALUES (?, ?, ?)
                     RETURNING id_portafolio',
                    [
                        $idUsuario,
                        'Portafolio de ' . $user->name,
                        'portafolio-' . $idUsuario . '-' . time(),
                    ]
                );

                $idPortafolio = $insertedPortafolio->id_portafolio;
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
                'archivo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,zip|max:51200',
            ]);

            $insertedProyecto = DB::selectOne(
                'INSERT INTO "Proyecto" (
                    id_portafolio,
                    nombre_proyecto,
                    descripcion_proyecto,
                    rol_desarrollador,
                    fecha_inicio,
                    fecha_fin,
                    enlace_repositorio,
                    enlace_proyecto_activo,
                    estado_proyecto,
                    visibilidad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING id_proyecto',
                [
                    $idPortafolio,
                    $data['nombre_proyecto'],
                    $data['descripcion_proyecto'],
                    $data['rol_desarrollador'] ?? null,
                    $data['fecha_inicio'] ?? null,
                    $data['fecha_fin'] ?? null,
                    $data['enlace_repositorio'] ?? null,
                    $data['enlace_proyecto_activo'] ?? null,
                    $data['estado_proyecto'] ?? 'completado',
                    'publico',
                ]
            );

            $idProyecto = $insertedProyecto->id_proyecto;

            if ($request->hasFile('archivo')) {
                $file = $request->file('archivo');
                $nombreArchivo = mb_convert_encoding($file->getClientOriginalName(), 'UTF-8', 'UTF-8');
                $mimeTipo = mb_convert_encoding((string) $file->getClientMimeType(), 'UTF-8', 'UTF-8');

                $path = $file->storeAs(
                    'proyectos/' . $idProyecto,
                    $nombreArchivo,
                    'public'
                );

                $url = Storage::disk('public')->url($path);

                DB::insert(
                    'INSERT INTO "Evidencia_Digital" (
                        id_proyecto,
                        id_usuario,
                        tipo_evidencia,
                        titulo,
                        url_enlace,
                        nombre_archivo,
                        tipo_mime
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $idProyecto,
                        $idUsuario,
                        'documento',
                        'Evidencia de ' . $data['nombre_proyecto'],
                        $url,
                        $nombreArchivo,
                        $mimeTipo,
                    ]
                );
            }

            return response()->json([
                'message' => 'Proyecto creado exitosamente.',
                'id' => $idProyecto,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $this->sanitizeUtf8($e->errors());
            file_put_contents(storage_path('logs/validation_errors.txt'), print_r($errors, true));
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $errors
            ], 422);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $safeMessage = rtrim(mb_convert_encoding($message, 'UTF-8', 'ISO-8859-1,Windows-1252,UTF-8'));

            return response()->json([
                'message' => $safeMessage ?: 'Error al crear el proyecto.',
                'trace' => $this->sanitizeUtf8($e->getTraceAsString()),
            ], 500);
        }
    }

    private function sanitizeUtf8(mixed $data): mixed
    {
        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitizedKey = is_string($key) ? mb_convert_encoding($key, 'UTF-8', 'UTF-8') : $key;
                $sanitized[$sanitizedKey] = $this->sanitizeUtf8($value);
            }
            return $sanitized;
        }
        return $data;
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        $proyecto = DB::selectOne(
            'SELECT p.id_proyecto
            FROM "Proyecto" p
            JOIN "Portafolio" pf ON p.id_portafolio = pf.id_portafolio
            WHERE p.id_proyecto = ? AND pf.id_usuario = ?',
            [$id, $idUsuario]
        );

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        Storage::disk('public')->deleteDirectory('proyectos/' . $id);
        DB::delete('DELETE FROM "Proyecto" WHERE id_proyecto = ?', [$id]);

        return response()->json(['message' => 'Proyecto eliminado exitosamente.']);
    }
}
