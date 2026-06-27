<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminDashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $this->isAdmin($user)) {
            return response()->json(['message' => 'Acceso restringido para administradores.'], 403);
        }

        $totalUsers = DB::table('users')->count();
        $developers = DB::table('users')->where('role', 'desarrollador')->count();
        $admins = DB::table('users')->whereIn('role', ['admin', 'administrador'])->count();
        $suspended = DB::table('Usuario')->where('estado_perfil', 'suspendido')->count();

        // pending: no existe un rol explicito de reclutador en el esquema actual.
        $recruiters = 0;

        $recentUsers = DB::table('users')
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'role' => $item->role,
                'created_at' => $item->created_at
                    ? Carbon::parse($item->created_at)->format('d/m/Y H:i')
                    : null,
            ])
            ->values();

        $activePortfolios = DB::table('Portafolio')->where('estado', 'publicado')->count();
        $pendingReports = DB::table('Reporte')->count();

        $evidenceCounts = DB::table('Evidencia_Digital')
            ->select('estado_revision', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_revision')
            ->pluck('total', 'estado_revision')
            ->all();

        $pendingEvidences = (int) ($evidenceCounts['en_revision'] ?? 0);
        $verifiedEvidences = (int) ($evidenceCounts['verificado'] ?? 0);
        $rejectedEvidences = (int) ($evidenceCounts['rechazado'] ?? 0);
        $totalEvidences = $pendingEvidences + $verifiedEvidences + $rejectedEvidences;

        $systemLoad = $totalEvidences > 0
            ? round(($pendingEvidences / $totalEvidences) * 100, 1)
            : 0.0;

        $moderationPreview = DB::table('Evidencia_Digital')
            ->leftJoin('Proyecto', 'Evidencia_Digital.id_proyecto', '=', 'Proyecto.id_proyecto')
            ->leftJoin('Usuario', 'Evidencia_Digital.id_usuario', '=', 'Usuario.id_usuario')
            ->select(
                'Evidencia_Digital.id_evidencia',
                'Evidencia_Digital.titulo',
                'Evidencia_Digital.tipo_evidencia',
                'Evidencia_Digital.estado_revision',
                'Evidencia_Digital.url_enlace',
                'Proyecto.nombre_proyecto',
                'Usuario.nombre_completo',
                'Usuario.correo',
                'Evidencia_Digital.fecha_carga'
            )
            ->orderByDesc('Evidencia_Digital.fecha_carga')
            ->limit(4)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id_evidencia,
                'title' => $item->titulo,
                'type' => $item->tipo_evidencia,
                'status' => $item->estado_revision,
                'file_url' => $this->resolveEvidenceUrl($item->url_enlace),
                'project' => $item->nombre_proyecto,
                'owner' => $item->nombre_completo ?: $item->correo,
                'created_at' => $item->fecha_carga
                    ? Carbon::parse($item->fecha_carga)->format('d/m/Y H:i')
                    : null,
            ])
            ->values();

        $technologyStats = DB::table('Tecnologia_Proyecto')
            ->join('Tecnologia', 'Tecnologia.id_tecnologia', '=', 'Tecnologia_Proyecto.id_tecnologia')
            ->select('Tecnologia.nombre_tecnologia', DB::raw('COUNT(*) as total'))
            ->groupBy('Tecnologia.nombre_tecnologia')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $totalTechUsage = $technologyStats->sum('total') ?: 1;
        $technologyPopularity = $technologyStats->map(fn ($item) => [
            'label' => $item->nombre_tecnologia,
            'count' => (int) $item->total,
            'percentage' => round(($item->total / $totalTechUsage) * 100),
        ])->values();

        $userGrowth = $this->buildUserGrowth();
        $securityEvents = $this->buildSecurityEvents();

        return response()->json([
            'stats' => [
                'total_users' => $totalUsers,
                'developers' => $developers,
                'recruiters' => $recruiters,
                'suspended' => $suspended,
                'admins' => $admins,
            ],
            'system' => [
                'active_portfolios' => $activePortfolios,
                'system_load' => $systemLoad,
                'pending_reports' => $pendingReports + $pendingEvidences,
            ],
            'recent_users' => $recentUsers,
            'moderation' => [
                'pending' => $pendingEvidences,
                'verified' => $verifiedEvidences,
                'rejected' => $rejectedEvidences,
                'latest' => $moderationPreview,
            ],
            'analytics' => [
                'technology_popularity' => $technologyPopularity,
                'user_growth' => $userGrowth,
                'project_status' => $this->buildProjectStatus(),
                'evidence_status' => [
                    ['label' => 'En revisión', 'value' => $pendingEvidences],
                    ['label' => 'Verificadas', 'value' => $verifiedEvidences],
                    ['label' => 'Rechazadas', 'value' => $rejectedEvidences],
                ],
            ],
            'security' => $securityEvents,
        ]);
    }

    public function createAdmin(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor || ! $this->isAdmin($actor)) {
            return response()->json(['message' => 'Solo un administrador puede crear cuentas admin.'], 403);
        }

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^(?=.*\pL)[\pL\s]+$/u'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/^\S+$/'],
        ], [
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
        ]);

        $admin = User::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
            'role' => 'admin',
        ]);

        return response()->json([
            'message' => 'Administrador creado correctamente.',
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
            ],
        ], 201);
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'administrador'], true);
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function buildUserGrowth(): array
    {
        $months = collect(range(0, 9))
            ->map(fn ($offset) => Carbon::now()->subMonths(9 - $offset)->startOfMonth())
            ->values();

        $rangeStart = $months->first()?->copy()->startOfMonth();

        if (! $rangeStart) {
            return [];
        }

        $rows = DB::table('users')
            ->select(DB::raw("DATE_TRUNC('month', created_at) as month"), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $rangeStart)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy(fn ($item) => Carbon::parse($item->month)->format('Y-m'));

        return $months->map(function (Carbon $month) use ($rows) {
            $key = $month->format('Y-m');
            $total = $rows[$key]->total ?? 0;

            return [
                'label' => ucfirst($month->locale('es')->isoFormat('MMM')),
                'value' => (int) $total,
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function buildProjectStatus(): array
    {
        $rows = DB::table('Proyecto')
            ->select('estado_revision', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_revision')
            ->get()
            ->keyBy('estado_revision');

        return [
            ['label' => 'En revisión', 'value' => (int) ($rows['en_revision']->total ?? 0)],
            ['label' => 'Verificados', 'value' => (int) ($rows['verificado']->total ?? 0)],
            ['label' => 'Rechazados', 'value' => (int) ($rows['rechazado']->total ?? 0)],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildSecurityEvents(): array
    {
        return DB::table('Actividad_Sistema')
            ->leftJoin('Usuario', 'Actividad_Sistema.id_usuario', '=', 'Usuario.id_usuario')
            ->select(
                'Actividad_Sistema.tipo_actividad',
                'Actividad_Sistema.descripcion',
                'Actividad_Sistema.fecha_actividad',
                'Usuario.correo'
            )
            ->orderByDesc('Actividad_Sistema.fecha_actividad')
            ->limit(6)
            ->get()
            ->map(fn ($item) => [
                'title' => $item->tipo_actividad,
                'user' => $item->correo ?? 'Sistema',
                'details' => $item->descripcion ?: 'Registro de auditoria.',
                'date' => $item->fecha_actividad
                    ? Carbon::parse($item->fecha_actividad)->format('d/m/Y H:i')
                    : null,
            ])
            ->values()
            ->all();
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
