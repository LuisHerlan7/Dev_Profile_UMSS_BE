<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EvidenceModerationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $this->isAdmin($user)) {
            return response()->json(['message' => 'Acceso restringido para administradores.'], 403);
        }

        $status = $request->query('status', 'en_revision');
        $page = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('per_page', 6), 1), 12);

        $query = DB::table('Evidencia_Digital')
            ->leftJoin('Proyecto', 'Evidencia_Digital.id_proyecto', '=', 'Proyecto.id_proyecto')
            ->leftJoin('Usuario', 'Evidencia_Digital.id_usuario', '=', 'Usuario.id_usuario')
            ->select(
                'Evidencia_Digital.id_evidencia',
                'Evidencia_Digital.titulo',
                'Evidencia_Digital.tipo_evidencia',
                'Evidencia_Digital.estado_revision',
                'Evidencia_Digital.url_enlace',
                'Evidencia_Digital.nombre_archivo',
                'Evidencia_Digital.tipo_mime',
                'Evidencia_Digital.tamaño_archivo',
                'Evidencia_Digital.fecha_carga',
                'Proyecto.id_proyecto',
                'Proyecto.nombre_proyecto',
                'Proyecto.estado_revision as estado_proyecto',
                'Usuario.nombre_completo',
                'Usuario.correo'
            )
            ->orderByDesc('Evidencia_Digital.fecha_carga');

        if ($status) {
            $query->where('Evidencia_Digital.estado_revision', $status);
        }

        $total = $query->count();
        $records = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id_evidencia,
                'title' => $item->titulo,
                'type' => $item->tipo_evidencia,
                'status' => $item->estado_revision,
                'file_url' => $this->resolveEvidenceUrl($item->url_enlace),
                'file_name' => $item->nombre_archivo,
                'mime' => $item->tipo_mime,
                'size' => $item->tamaño_archivo,
                'project' => [
                    'id' => $item->id_proyecto,
                    'name' => $item->nombre_proyecto,
                    'status' => $item->estado_proyecto,
                ],
                'owner' => [
                    'name' => $item->nombre_completo,
                    'email' => $item->correo,
                ],
                'created_at' => $item->fecha_carga
                    ? Carbon::parse($item->fecha_carga)->format('d/m/Y H:i')
                    : null,
            ])
            ->values();

        return response()->json([
            'data' => $records,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => $perPage ? (int) ceil($total / $perPage) : 1,
            ],
        ]);
    }

    public function update(Request $request, int $evidenceId): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $this->isAdmin($user)) {
            return response()->json(['message' => 'Acceso restringido para administradores.'], 403);
        }

        $payload = $request->validate([
            'status' => ['required', 'in:en_revision,verificado,rechazado'],
        ]);

        $evidence = DB::table('Evidencia_Digital')->where('id_evidencia', $evidenceId)->first();

        if (! $evidence) {
            return response()->json(['message' => 'Evidencia no encontrada.'], 404);
        }

        DB::table('Evidencia_Digital')
            ->where('id_evidencia', $evidenceId)
            ->update(['estado_revision' => $payload['status']]);

        if ($evidence->id_proyecto) {
            $this->syncProjectStatus((int) $evidence->id_proyecto);
        }

        return response()->json(['message' => 'Estado de evidencia actualizado.']);
    }

    private function syncProjectStatus(int $projectId): void
    {
        $statuses = DB::table('Evidencia_Digital')
            ->where('id_proyecto', $projectId)
            ->pluck('estado_revision')
            ->all();

        if ($statuses === []) {
            return;
        }

        if (in_array('rechazado', $statuses, true)) {
            $status = 'rechazado';
        } elseif (count(array_unique($statuses)) === 1 && $statuses[0] === 'verificado') {
            $status = 'verificado';
        } else {
            $status = 'en_revision';
        }

        DB::table('Proyecto')->where('id_proyecto', $projectId)->update(['estado_revision' => $status]);
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'administrador'], true);
    }

    private function resolveEvidenceUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (str_starts_with($url, 'http')) {
            return $url;
        }

        $baseUrl = rtrim((string) env('APP_URL', 'http://127.0.0.1:9200'), '/');

        return $baseUrl.$url;
    }
}
