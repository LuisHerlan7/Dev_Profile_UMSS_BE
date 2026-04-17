<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeveloperDashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No existe una sesion activa.'], 401);
        }

        if ($user->role !== 'desarrollador') {
            return response()->json(['message' => 'Acceso restringido al panel de desarrolladores.'], 403);
        }

        $usuario = DB::table('Usuario')->where('correo', $user->email)->first();
        $usuarioId = $usuario?->id_usuario;

        $portafolio = $usuarioId
            ? DB::table('Portafolio')->where('id_usuario', $usuarioId)->first()
            : null;
        $portafolioId = $portafolio?->id_portafolio;

        $projectCount = $portafolioId
            ? DB::table('Proyecto')->where('id_portafolio', $portafolioId)->count()
            : 0;

        $skillsQuery = $usuarioId ? DB::table('Habilidad')->where('id_usuario', $usuarioId) : null;
        $skillsCount = $skillsQuery ? $skillsQuery->count() : 0;

        $recentProjects = $portafolioId
            ? DB::table('Proyecto')
                ->where('id_portafolio', $portafolioId)
                ->orderByDesc('fecha_creacion')
                ->limit(3)
                ->get()
            : collect();

        $recentProjectIds = $recentProjects->pluck('id_proyecto')->all();
        $techMap = $this->mapProjectTechnologies($recentProjectIds);

        $recentProjectsPayload = $recentProjects->map(function ($project) use ($techMap) {
            $projectId = $project->id_proyecto;
            $techStack = $techMap[$projectId] ?? [];

            return [
                'id' => (string) $projectId,
                'name' => $project->nombre_proyecto,
                'stack' => $techStack
                    ? implode(', ', array_slice($techStack, 0, 2))
                    : ($project->rol_desarrollador ?: 'Sin tecnologia'),
                'updated_at' => $this->formatDate($project->fecha_creacion),
            ];
        })->values();

        $projects = $portafolioId
            ? DB::table('Proyecto')
                ->where('id_portafolio', $portafolioId)
                ->orderByDesc('fecha_creacion')
                ->limit(8)
                ->get()
            : collect();

        $projectIds = $projects->pluck('id_proyecto')->all();
        $projectTechMap = $this->mapProjectTechnologies($projectIds);
        $projectEvidenceMap = $this->mapProjectEvidenceSummary($projectIds);

        $projectsPayload = $projects->map(function ($project) use ($projectTechMap, $projectEvidenceMap) {
            $projectId = $project->id_proyecto;
            $tags = $projectTechMap[$projectId] ?? [];
            $evidenceSummary = $projectEvidenceMap[$projectId] ?? [
                'total' => 0,
                'pending' => 0,
                'verified' => 0,
                'rejected' => 0,
            ];

            return [
                'id' => (string) $projectId,
                'title' => $project->nombre_proyecto,
                'category' => $tags[0] ?? 'PROYECTO',
                'summary' => $project->descripcion_proyecto ?: 'Proyecto registrado en tu portafolio.',
                'role' => $project->rol_desarrollador ?: 'Desarrollador',
                'tags' => $tags,
                'status' => $project->estado_revision ?? 'en_revision',
                'evidence_summary' => $evidenceSummary,
            ];
        })->values();

        $skills = $usuarioId
            ? DB::table('Habilidad')
                ->where('id_usuario', $usuarioId)
                ->orderByDesc('nivel_dominio')
                ->get()
            : collect();

        [$technicalSkills, $softSkills] = $this->splitSkills($skills);

        $experienceEntries = $this->buildExperienceEntries($usuarioId);

        $profileCompletion = $this->calculateProfileCompletion($usuario, $projectCount, $skillsCount, $experienceEntries);

        $evidencesPayload = $this->buildEvidencePayload($usuarioId);

        return response()->json([
            'metrics' => [
                'projects' => $projectCount,
                'skills' => $skillsCount,
                // pending: aun no existe tracking de vistas en base de datos.
                'profile_views' => 0,
            ],
            'profile' => [
                'completion' => $profileCompletion,
                'next_step' => $this->resolveNextStep($usuario, $projectCount, $skillsCount, $experienceEntries),
            ],
            'recent_projects' => $recentProjectsPayload,
            'projects' => $projectsPayload,
            'evidences' => $evidencesPayload,
            'skills' => [
                'technical' => $technicalSkills,
                'soft' => $softSkills,
            ],
            'experience' => $experienceEntries,
        ]);
    }

    /**
     * @param array<int, int> $projectIds
     * @return array<int, array<int, string>>
     */
    private function mapProjectTechnologies(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $rows = DB::table('Tecnologia_Proyecto')
            ->join('Tecnologia', 'Tecnologia.id_tecnologia', '=', 'Tecnologia_Proyecto.id_tecnologia')
            ->whereIn('Tecnologia_Proyecto.id_proyecto', $projectIds)
            ->select('Tecnologia_Proyecto.id_proyecto', 'Tecnologia.nombre_tecnologia')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $projectId = $row->id_proyecto;
            $map[$projectId] = $map[$projectId] ?? [];
            $map[$projectId][] = $row->nombre_tecnologia;
        }

        return $map;
    }

    /**
     * @param array<int, int> $projectIds
     * @return array<int, array<string, int>>
     */
    private function mapProjectEvidenceSummary(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $rows = DB::table('Evidencia_Digital')
            ->select('id_proyecto', 'estado_revision', DB::raw('COUNT(*) as total'))
            ->whereIn('id_proyecto', $projectIds)
            ->groupBy('id_proyecto', 'estado_revision')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $projectId = $row->id_proyecto;
            $map[$projectId] = $map[$projectId] ?? [
                'total' => 0,
                'pending' => 0,
                'verified' => 0,
                'rejected' => 0,
            ];

            $map[$projectId]['total'] += (int) $row->total;

            if ($row->estado_revision === 'verificado') {
                $map[$projectId]['verified'] += (int) $row->total;
            } elseif ($row->estado_revision === 'rechazado') {
                $map[$projectId]['rejected'] += (int) $row->total;
            } else {
                $map[$projectId]['pending'] += (int) $row->total;
            }
        }

        return $map;
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $skills
     * @return array{0: array<int, array<string, int|string>>, 1: array<int, string>}
     */
    private function splitSkills($skills): array
    {
        $technicalSkills = [];
        $softSkills = [];

        foreach ($skills as $skill) {
            if ($skill->tipo_habilidad === 'blanda') {
                $softSkills[] = $skill->nombre_habilidad;
                continue;
            }

            $technicalSkills[] = [
                'name' => $skill->nombre_habilidad,
                'level' => $this->resolveSkillLevel($skill->nivel_dominio),
                'progress' => $this->resolveSkillProgress($skill->nivel_dominio),
            ];
        }

        return [$technicalSkills, $softSkills];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildExperienceEntries(?int $usuarioId): array
    {
        if (! $usuarioId) {
            return [];
        }

        $entries = [];

        $experiences = DB::table('Experiencia_Laboral')
            ->where('id_usuario', $usuarioId)
            ->orderByDesc('fecha_inicio')
            ->get();

        foreach ($experiences as $experience) {
            $entries[] = [
                'id' => 'work-'.$experience->id_experiencia,
                'type' => 'work',
                'title' => $experience->nombre_empresa,
                'subtitle' => $experience->titulo_puesto,
                'period' => $this->formatPeriod($experience->fecha_inicio, $experience->fecha_fin, $experience->es_trabajo_actual),
                'description' => $experience->descripcion_puesto ?: 'Experiencia profesional registrada en tu perfil.',
            ];
        }

        $studies = DB::table('Formacion_Academica')
            ->where('id_usuario', $usuarioId)
            ->orderByDesc('fecha_inicio')
            ->get();

        foreach ($studies as $study) {
            $entries[] = [
                'id' => 'study-'.$study->id_formacion,
                'type' => 'study',
                'title' => $study->institucion,
                'subtitle' => $study->carrera_especialidad,
                'period' => $this->formatPeriod($study->fecha_inicio, $study->fecha_fin, $study->actualmente_estudiante),
                'description' => $study->descripcion ?: 'Formacion academica registrada en tu perfil.',
            ];
        }

        return $entries;
    }

    private function resolveSkillLevel(?string $level): string
    {
        return match ($level) {
            'basico' => 'Basico',
            'intermedio' => 'Intermedio',
            'avanzado' => 'Avanzado',
            'experto' => 'Experto',
            default => 'Intermedio',
        };
    }

    private function resolveSkillProgress(?string $level): int
    {
        return match ($level) {
            'basico' => 35,
            'intermedio' => 60,
            'avanzado' => 82,
            'experto' => 96,
            default => 60,
        };
    }

    private function calculateProfileCompletion(?object $usuario, int $projectCount, int $skillsCount, array $experienceEntries): int
    {
        $score = 0;

        if ($usuario && ! empty($usuario->biografia)) {
            $score += 25;
        }

        if ($projectCount > 0) {
            $score += 25;
        }

        if ($skillsCount > 0) {
            $score += 25;
        }

        if ($experienceEntries !== []) {
            $score += 25;
        }

        return $score > 0 ? $score : 25;
    }

    private function resolveNextStep(?object $usuario, int $projectCount, int $skillsCount, array $experienceEntries): string
    {
        if (! $usuario || empty($usuario->biografia)) {
            return 'Agrega una biografia para completar tu perfil.';
        }

        if ($projectCount === 0) {
            return 'Comparte tu primer proyecto para fortalecer tu portafolio.';
        }

        if ($skillsCount === 0) {
            return 'Incluye tus habilidades principales para que el perfil sea mas visible.';
        }

        if ($experienceEntries === []) {
            return 'Registra tu experiencia o formacion para completar tu historial.';
        }

        return 'Tu perfil luce muy bien. Mantente activo para destacar.';
    }

    private function formatDate($date): string
    {
        if (! $date) {
            return 'Sin fecha';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    private function formatPeriod($start, $end, $isCurrent): string
    {
        $startLabel = $start ? Carbon::parse($start)->format('M Y') : 'Fecha no definida';
        $endLabel = $isCurrent ? 'Actualidad' : ($end ? Carbon::parse($end)->format('M Y') : 'Actualidad');

        return sprintf('%s - %s', $startLabel, $endLabel);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function buildEvidencePayload(?int $usuarioId): array
    {
        if (! $usuarioId) {
            return [];
        }

        return DB::table('Evidencia_Digital')
            ->leftJoin('Proyecto', 'Evidencia_Digital.id_proyecto', '=', 'Proyecto.id_proyecto')
            ->where('Evidencia_Digital.id_usuario', $usuarioId)
            ->orderByDesc('Evidencia_Digital.fecha_carga')
            ->limit(12)
            ->select(
                'Evidencia_Digital.id_evidencia',
                'Evidencia_Digital.titulo',
                'Evidencia_Digital.tipo_evidencia',
                'Evidencia_Digital.estado_revision',
                'Evidencia_Digital.url_enlace',
                'Evidencia_Digital.fecha_carga',
                'Proyecto.nombre_proyecto'
            )
            ->get()
            ->map(fn ($item) => [
                'id' => (string) $item->id_evidencia,
                'title' => $item->titulo,
                'type' => $item->tipo_evidencia,
                'status' => $item->estado_revision,
                'file_url' => $this->resolveEvidenceUrl($item->url_enlace),
                'project' => $item->nombre_proyecto ?: 'Proyecto',
                'created_at' => $item->fecha_carga
                    ? Carbon::parse($item->fecha_carga)->format('d/m/Y H:i')
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
