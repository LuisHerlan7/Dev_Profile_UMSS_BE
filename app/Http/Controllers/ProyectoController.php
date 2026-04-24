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
                'descripcion_tecnica' => 'nullable|string',
                'rol_desarrollador' => 'nullable|string|max:100',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
                'enlace_repositorio' => 'nullable|url|max:255',
                'enlace_proyecto_activo' => 'nullable|url|max:255',
                'estado_proyecto' => 'nullable|in:en_desarrollo,completado,pausado',
                'evidences' => 'nullable|array',
                'evidences.*' => 'file|max:51200',
                'evidence_folders' => 'nullable|array',
                'technologies' => 'nullable|array',
            ]);

            $insertedProyecto = DB::selectOne(
                'INSERT INTO "Proyecto" (
                    id_portafolio,
                    nombre_proyecto,
                    descripcion_proyecto,
                    descripcion_tecnica,
                    rol_desarrollador,
                    fecha_inicio,
                    fecha_fin,
                    enlace_repositorio,
                    enlace_proyecto_activo,
                    estado_proyecto,
                    visibilidad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING id_proyecto',
                [
                    $idPortafolio,
                    $data['nombre_proyecto'],
                    $data['descripcion_proyecto'],
                    $data['descripcion_tecnica'] ?? null,
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

            // Sync Technologies
            if (!empty($request->input('technologies')) && is_array($request->input('technologies'))) {
                $this->syncTechnologies($idProyecto, $request->input('technologies'));
            }

            // Handle multiple files
            if ($request->hasFile('evidences')) {
                $files = $request->file('evidences');
                $folders = $request->input('evidence_folders', []);

                foreach ($files as $index => $file) {
                    $nombreArchivo = mb_convert_encoding($file->getClientOriginalName(), 'UTF-8', 'UTF-8');
                    $mimeTipo = mb_convert_encoding((string) $file->getClientMimeType(), 'UTF-8', 'UTF-8');
                    $folder = $folders[$index] ?? 'root/';

                    $path = $file->storeAs(
                        'evidences/' . $idProyecto,
                        time() . '-' . $nombreArchivo,
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
                            tipo_mime,
                            fecha_carga,
                            estado_revision
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $idProyecto,
                            $idUsuario,
                            $this->resolveType($mimeTipo),
                            $folder . $nombreArchivo,
                            $url,
                            $nombreArchivo,
                            $mimeTipo,
                            now(),
                            'en_revision'
                        ]
                    );
                }
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

    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        $proyecto = DB::selectOne(
            'SELECT p.*
            FROM "Proyecto" p
            JOIN "Portafolio" pf ON p.id_portafolio = pf.id_portafolio
            WHERE p.id_proyecto = ? AND pf.id_usuario = ?',
            [$id, $idUsuario]
        );

        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $tecnologias = DB::select(
            'SELECT t.nombre_tecnologia
            FROM "Tecnologia" t
            JOIN "Tecnologia_Proyecto" tp ON t.id_tecnologia = tp.id_tecnologia
            WHERE tp.id_proyecto = ?',
            [$id]
        );

        $evidencias = DB::select(
            'SELECT id_evidencia AS id, titulo, url_enlace, nombre_archivo, tipo_mime, fecha_carga, visibilidad, estado_revision
            FROM "Evidencia_Digital"
            WHERE id_proyecto = ?',
            [$id]
        );

        return response()->json([
            'proyecto' => $proyecto,
            'tecnologias' => array_column($tecnologias, 'nombre_tecnologia'),
            'evidencias' => $evidencias
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

            $proyecto = DB::selectOne(
                'SELECT p.id_proyecto
                FROM "Proyecto" p
                JOIN "Portafolio" pf ON p.id_portafolio = pf.id_portafolio
                WHERE p.id_proyecto = ? AND pf.id_usuario = ?',
                [$id, $idUsuario]
            );

            if (!$proyecto) {
                return response()->json(['message' => 'No autorizado o proyecto no encontrado.'], 403);
            }

            $data = $request->validate([
                'nombre_proyecto' => 'required|string|max:150',
                'descripcion_proyecto' => 'required|string',
                'descripcion_tecnica' => 'nullable|string',
                'rol_desarrollador' => 'nullable|string|max:100',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
                'enlace_repositorio' => 'nullable|url|max:255',
                'enlace_proyecto_activo' => 'nullable|url|max:255',
                'estado_proyecto' => 'nullable|in:en_desarrollo,completado,pausado',
                'evidences' => 'nullable|array',
                'evidences.*' => 'file|max:51200',
                'evidence_folders' => 'nullable|array',
                'technologies' => 'nullable|array',
                'deleted_evidences' => 'nullable|array',
            ]);

            DB::update(
                'UPDATE "Proyecto" SET
                    nombre_proyecto = ?,
                    descripcion_proyecto = ?,
                    descripcion_tecnica = ?,
                    rol_desarrollador = ?,
                    fecha_inicio = ?,
                    fecha_fin = ?,
                    enlace_repositorio = ?,
                    enlace_proyecto_activo = ?,
                    estado_proyecto = ?
                WHERE id_proyecto = ?',
                [
                    $data['nombre_proyecto'],
                    $data['descripcion_proyecto'],
                    $data['descripcion_tecnica'] ?? null,
                    $data['rol_desarrollador'] ?? null,
                    $data['fecha_inicio'] ?? null,
                    $data['fecha_fin'] ?? null,
                    $data['enlace_repositorio'] ?? null,
                    $data['enlace_proyecto_activo'] ?? null,
                    $data['estado_proyecto'] ?? 'completado',
                    $id
                ]
            );

            // Handle deleted evidences
            if (!empty($data['deleted_evidences'])) {
                foreach ($data['deleted_evidences'] as $evId) {
                    DB::delete('DELETE FROM "Evidencia_Digital" WHERE id_evidencia = ? AND id_proyecto = ?', [$evId, $id]);
                }
            }

            // Sync Technologies
            if (isset($data['technologies'])) {
                $this->syncTechnologies($id, $data['technologies']);
            }

            // Handle new files
            if ($request->hasFile('evidences')) {
                $files = $request->file('evidences');
                $folders = $request->input('evidence_folders', []);

                foreach ($files as $index => $file) {
                    $nombreArchivo = mb_convert_encoding($file->getClientOriginalName(), 'UTF-8', 'UTF-8');
                    $mimeTipo = mb_convert_encoding((string) $file->getClientMimeType(), 'UTF-8', 'UTF-8');
                    $folder = $folders[$index] ?? 'root/';

                    $path = $file->storeAs(
                        'evidences/' . $id,
                        time() . '-' . $nombreArchivo,
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
                            tipo_mime,
                            fecha_carga,
                            estado_revision
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $id,
                            $idUsuario,
                            $this->resolveType($mimeTipo),
                            $folder . $nombreArchivo,
                            $url,
                            $nombreArchivo,
                            $mimeTipo,
                            now(),
                            'en_revision'
                        ]
                    );
                }
            }

            return response()->json([
                'message' => 'Proyecto actualizado exitosamente.',
            ]);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $safeMessage = rtrim(mb_convert_encoding($message, 'UTF-8', 'ISO-8859-1,Windows-1252,UTF-8'));
            return response()->json([
                'message' => $safeMessage ?: 'Error al actualizar el proyecto.',
                'trace' => $this->sanitizeUtf8($e->getTraceAsString()),
            ], 500);
        }
    }

    public function updateVisibility(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        $project = DB::selectOne(
            'SELECT p.id_proyecto
             FROM "Proyecto" p
             JOIN "Portafolio" pf ON p.id_portafolio = pf.id_portafolio
             WHERE p.id_proyecto = ? AND pf.id_usuario = ?',
            [$id, $idUsuario]
        );

        if (! $project) {
            return response()->json(['message' => 'Proyecto no encontrado o no autorizado.'], 404);
        }

        $data = $request->validate([
            'visibilidad' => 'required|in:publico,privado',
        ]);

        DB::update(
            'UPDATE "Proyecto" SET visibilidad = ? WHERE id_proyecto = ?',
            [$data['visibilidad'], $id]
        );

        return response()->json([
            'message' => 'Visibilidad actualizada correctamente.',
            'visibilidad' => $data['visibilidad'],
        ]);
    }

    public function updateEvidence(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

            $evidence = DB::selectOne(
                'SELECT * FROM "Evidencia_Digital" WHERE id_evidencia = ? AND id_usuario = ?',
                [$id, $idUsuario]
            );

            if (!$evidence) {
                return response()->json(['message' => 'Evidencia no encontrada o no autorizada.'], 404);
            }

            $data = $request->validate([
                'titulo' => 'nullable|string|max:200',
                'tipo_evidencia' => 'nullable|in:imagen,documento,video,enlace',
            ]);

            $updates = [];
            $params = [];

            if (isset($data['titulo'])) {
                $updates[] = 'titulo = ?';
                $params[] = $data['titulo'];
            }
            if (isset($data['tipo_evidencia'])) {
                $updates[] = 'tipo_evidencia = ?';
                $params[] = $data['tipo_evidencia'];
            }
            if (empty($updates)) {
                return response()->json(['message' => 'No hay campos para actualizar.'], 400);
            }

            $params[] = $id;
            $params[] = $idUsuario;

            DB::update(
                'UPDATE "Evidencia_Digital" SET ' . implode(', ', $updates) . ' WHERE id_evidencia = ? AND id_usuario = ?',
                $params
            );

            return response()->json([
                'message' => 'Evidencia actualizada correctamente.',
            ]);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $safeMessage = rtrim(mb_convert_encoding($message, 'UTF-8', 'ISO-8859-1,Windows-1252,UTF-8'));
            return response()->json([
                'message' => $safeMessage ?: 'Error al actualizar la evidencia.',
                'trace' => $this->sanitizeUtf8($e->getTraceAsString()),
            ], 500);
        }
    }

    private function resolveType($mime): string
    {
        if (str_starts_with($mime, 'image/')) return 'imagen';
        if (str_starts_with($mime, 'video/')) return 'video';
        return 'documento';
    }

    private function syncTechnologies(int $projectId, array $technologies): void
    {
        DB::delete('DELETE FROM "Tecnologia_Proyecto" WHERE id_proyecto = ?', [$projectId]);
        
        foreach ($technologies as $techName) {
            $tech = DB::selectOne('SELECT id_tecnologia FROM "Tecnologia" WHERE nombre_tecnologia = ?', [$techName]);
            
            if (!$tech) {
                $inserted = DB::selectOne(
                    'INSERT INTO "Tecnologia" (nombre_tecnologia, categoria) VALUES (?, ?) RETURNING id_tecnologia',
                    [$techName, 'otro']
                );
                $techId = $inserted->id_tecnologia;
            } else {
                $techId = $tech->id_tecnologia;
            }

            DB::insert(
                'INSERT INTO "Tecnologia_Proyecto" (id_proyecto, id_tecnologia, nivel_utilizacion) VALUES (?, ?, ?)',
                [$projectId, $techId, 'intermedio']
            );
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
