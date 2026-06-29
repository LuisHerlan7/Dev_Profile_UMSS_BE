<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user || $user->role !== 'desarrollador') {
            return response()->json(['message' => 'Acceso restringido para desarrolladores.'], 403);
        }

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'role' => ['nullable', 'string', 'max:100'],
            'repo_url' => ['nullable', 'url', 'max:255'],
            'live_url' => ['nullable', 'url', 'max:255'],
            'visibility' => ['nullable', 'in:publico,privado'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'technologies' => ['nullable', 'array'],
            'technologies.*' => ['string', 'max:100'],
            'evidences' => ['required'],
            'evidences.*' => ['file', 'max:51200'],
        ]);

        $usuario = DB::table('Usuario')->where('correo', $user->email)->first();
        $usuarioId = $usuario?->id_usuario;

        if (! $usuarioId) {
            return response()->json(['message' => 'No se encontro el perfil del desarrollador.'], 422);
        }

        $portafolioId = $this->ensurePortafolio($usuarioId, $user->name);

        DB::beginTransaction();

        try {
            $projectId = DB::table('Proyecto')->insertGetId([
                'id_portafolio' => $portafolioId,
                'nombre_proyecto' => $payload['name'],
                'descripcion_proyecto' => $payload['description'],
                'rol_desarrollador' => $payload['role'] ?? null,
                'enlace_repositorio' => $payload['repo_url'] ?? null,
                'enlace_proyecto_activo' => $payload['live_url'] ?? null,
                'visibilidad' => $payload['visibility'] ?? 'publico',
                'fecha_inicio' => $payload['start_date'] ?? null,
                'fecha_fin' => $payload['end_date'] ?? null,
                'fecha_creacion' => now(),
                'estado_revision' => 'en_revision',
            ], 'id_proyecto');

            $this->syncTechnologies($projectId, $payload['technologies'] ?? []);

            $evidenceFiles = $request->file('evidences', []);
            $evidencesPayload = $this->storeEvidenceFiles($projectId, $usuarioId, $evidenceFiles);

            DB::commit();

            return response()->json([
                'message' => 'Proyecto creado correctamente.',
                'project' => [
                    'id' => $projectId,
                    'name' => $payload['name'],
                    'status' => 'en_revision',
                ],
                'evidences' => $evidencesPayload,
            ], 201);
        } catch (\Throwable $exception) {
            DB::rollBack();

            return response()->json([
                'message' => 'No se pudo registrar el proyecto.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function uploadEvidence(Request $request, int $projectId): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user || $user->role !== 'desarrollador') {
            return response()->json(['message' => 'Acceso restringido para desarrolladores.'], 403);
        }

        $request->validate([
            'evidences' => ['required'],
            'evidences.*' => ['file', 'max:51200'],
        ]);

        $usuario = DB::table('Usuario')->where('correo', $user->email)->first();
        $usuarioId = $usuario?->id_usuario;

        if (! $usuarioId) {
            return response()->json(['message' => 'No se encontro el perfil del desarrollador.'], 422);
        }

        $project = DB::table('Proyecto')
            ->join('Portafolio', 'Proyecto.id_portafolio', '=', 'Portafolio.id_portafolio')
            ->where('Proyecto.id_proyecto', $projectId)
            ->where('Portafolio.id_usuario', $usuarioId)
            ->select('Proyecto.id_proyecto')
            ->first();

        if (! $project) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $evidenceFiles = $request->file('evidences', []);
        $evidencesPayload = $this->storeEvidenceFiles($projectId, $usuarioId, $evidenceFiles);

        DB::table('Proyecto')->where('id_proyecto', $projectId)->update(['estado_revision' => 'en_revision']);

        return response()->json([
            'message' => 'Evidencias cargadas correctamente.',
            'evidences' => $evidencesPayload,
        ], 201);
    }

    private function ensurePortafolio(int $usuarioId, string $userName): int
    {
        $existing = DB::table('Portafolio')->where('id_usuario', $usuarioId)->first();

        if ($existing) {
            return (int) $existing->id_portafolio;
        }

        $slug = Str::slug($userName.'-'.$usuarioId, '-');
        $url = rtrim((string) env('FRONTEND_URL', 'http://127.0.0.1:4200'), '/').'/portafolio/'.$slug;

        return (int) DB::table('Portafolio')->insertGetId([
            'id_usuario' => $usuarioId,
            'titulo_portafolio' => 'Portafolio de '.$userName,
            'descripcion_general' => 'Portafolio profesional generado automaticamente.',
            'url_publica' => $url,
            'estado' => 'publicado',
            'fecha_creacion' => now(),
        ], 'id_portafolio');
    }

    /**
     * @param array<int, string> $technologies
     */
    private function syncTechnologies(int $projectId, array $technologies): void
    {
        $clean = collect($technologies)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values();

        if ($clean->isEmpty()) {
            return;
        }

        foreach ($clean as $techName) {
            $tech = DB::table('Tecnologia')->where('nombre_tecnologia', $techName)->first();

            $techId = $tech?->id_tecnologia ?? DB::table('Tecnologia')->insertGetId([
                'nombre_tecnologia' => $techName,
                'categoria' => 'otro',
                'descripcion' => 'Registrada por el desarrollador.',
            ], 'id_tecnologia');

            DB::table('Tecnologia_Proyecto')->insert([
                'id_proyecto' => $projectId,
                'id_tecnologia' => $techId,
                'nivel_utilizacion' => 'intermedio',
            ]);
        }
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile> $evidenceFiles
     * @return array<int, array<string, mixed>>
     */
    private function storeEvidenceFiles(int $projectId, int $usuarioId, array $evidenceFiles): array
    {
        $payload = [];

        foreach ($evidenceFiles as $file) {
            $mime = $file->getMimeType();
            $tipo = $this->resolveEvidenceType($mime);
            $originalName = $file->getClientOriginalName();
            $filename = sprintf('%s-%s.%s', Carbon::now()->format('YmdHis'), Str::random(6), $file->getClientOriginalExtension());

            $path = $file->storeAs('evidences/'.$projectId, $filename, 'public');
            $relativeUrl = Storage::disk('public')->url($path);
            $baseUrl = rtrim((string) env('APP_URL', 'http://127.0.0.1:9200'), '/');
            $url = str_starts_with($relativeUrl, 'http') ? $relativeUrl : $baseUrl.$relativeUrl;

            $evidenceId = DB::table('Evidencia_Digital')->insertGetId([
                'id_proyecto' => $projectId,
                'id_usuario' => $usuarioId,
                'tipo_evidencia' => $tipo,
                'titulo' => pathinfo($originalName, PATHINFO_FILENAME) ?: 'Evidencia',
                'descripcion' => 'Evidencia cargada por el desarrollador.',
                'url_enlace' => $url,
                'nombre_archivo' => $originalName,
                'tipo_mime' => $mime,
                'tamaño_archivo' => $file->getSize(),
                'fecha_carga' => now(),
                'visibilidad' => 'publico',
                'estado_revision' => 'en_revision',
                'archivo' => '\x' . bin2hex(file_get_contents($file->getRealPath())),
            ], 'id_evidencia');

            $payload[] = [
                'id' => $evidenceId,
                'title' => pathinfo($originalName, PATHINFO_FILENAME) ?: 'Evidencia',
                'type' => $tipo,
                'status' => 'en_revision',
                'file_url' => $url,
            ];
        }

        return $payload;
    }

    private function resolveEvidenceType(?string $mime): string
    {
        if (! $mime) {
            return 'documento';
        }

        if (str_starts_with($mime, 'image/')) {
            return 'imagen';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (str_contains($mime, 'pdf')) {
            return 'documento';
        }

        return 'documento';
    }
}
