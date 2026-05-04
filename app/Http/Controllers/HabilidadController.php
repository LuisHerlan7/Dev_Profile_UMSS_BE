<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class HabilidadController extends Controller
{
    public function __construct(
        private readonly GeneradorUsuarioSync $generadorUsuarioSync
    ) {}

    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'desarrollador') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        $payload = $request->validate([
            'technical' => ['nullable', 'array'],
            'technical.*.name' => ['required', 'string', 'max:50'],
            'technical.*.level' => ['nullable', 'string', 'in:Principiante,Intermedio,Avanzado,Experto,Basico,basico,intermedio,avanzado,experto'],
            'technical.*.progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'technical.*.links' => ['nullable', 'array', 'max:5'],
            'technical.*.links.*.referenceType' => ['required_with:technical.*.links', 'string', 'in:project,formation'],
            'technical.*.links.*.referenceId' => ['required_with:technical.*.links', 'integer'],
            'technical.*.links.*.label' => ['required_with:technical.*.links', 'string', 'max:200'],
            'soft' => ['nullable', 'array'],
            'soft.*.name' => ['required', 'string', 'max:50'],
            'soft.*.level' => ['nullable', 'string', 'in:Principiante,Intermedio,Avanzado,Experto,Basico,basico,intermedio,avanzado,experto'],
            'soft.*.progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'soft.*.links' => ['nullable', 'array', 'max:5'],
            'soft.*.links.*.referenceType' => ['required_with:soft.*.links', 'string', 'in:experience,formation'],
            'soft.*.links.*.referenceId' => ['required_with:soft.*.links', 'integer'],
            'soft.*.links.*.label' => ['required_with:soft.*.links', 'string', 'max:200'],
        ]);

        $technical = array_map(function (array $skill): array {
            return [
                'name' => trim((string) ($skill['name'] ?? '')),
                'level' => (string) ($skill['level'] ?? 'Intermedio'),
                'progress' => isset($skill['progress']) ? (int) $skill['progress'] : null,
                'links' => array_values($skill['links'] ?? []),
            ];
        }, $payload['technical'] ?? []);

        $soft = array_map(function (array $skill): array {
            return [
                'name' => trim((string) ($skill['name'] ?? '')),
                'level' => (string) ($skill['level'] ?? 'Intermedio'),
                'progress' => isset($skill['progress']) ? (int) $skill['progress'] : null,
                'links' => array_values($skill['links'] ?? []),
            ];
        }, $payload['soft'] ?? []);

        $allNames = [];
        foreach ([$technical, $soft] as $bucket) {
            foreach ($bucket as $skill) {
                $name = $skill['name'] ?? '';
                if ($name === '') {
                    throw ValidationException::withMessages([
                        'skills' => ['No se permite guardar una habilidad sin nombre.'],
                    ]);
                }

                $normalized = mb_strtolower($name, 'UTF-8');
                if (in_array($normalized, $allNames, true)) {
                    throw ValidationException::withMessages([
                        'skills' => ['No se permiten habilidades duplicadas.'],
                    ]);
                }

                $allNames[] = $normalized;
            }
        }

        $mapLevel = function($level) {
            $l = strtolower($level);
            if ($l === 'principiante' || $l === 'basico') return 'basico';
            if ($l === 'avanzado') return 'avanzado';
            if ($l === 'experto') return 'experto';
            return 'intermedio';
        };

        $resolveProgress = function (?int $progress, string $level) use ($mapLevel): int {
            if ($progress !== null) {
                return max(0, min(100, $progress));
            }

            return match ($mapLevel($level)) {
                'basico' => 25,
                'avanzado' => 75,
                'experto' => 100,
                default => 50,
            };
        };

        return DB::transaction(function () use ($idUsuario, $technical, $soft, $mapLevel, $resolveProgress) {
            // Eliminar todas las habilidades actuales del usuario
            DB::delete('DELETE FROM "Habilidad" WHERE id_usuario = ?', [$idUsuario]);

            foreach ($technical as $tech) {
                if (!empty($tech['name'])) {
                    $insertedSkill = DB::selectOne(
                        'INSERT INTO "Habilidad" (
                            id_usuario,
                            nombre_habilidad,
                            tipo_habilidad,
                            nivel_dominio,
                            porcentaje_dominio,
                            estado
                        ) VALUES (?, ?, ?, ?, ?, ?)
                        RETURNING id_habilidad',
                        [
                            $idUsuario,
                            $tech['name'],
                            'tecnica',
                            $mapLevel($tech['level'] ?? 'Intermedio'),
                            $resolveProgress($tech['progress'], $tech['level'] ?? 'Intermedio'),
                            'activo',
                        ]
                    );

                    $this->syncSkillLinks(
                        $idUsuario,
                        (int) $insertedSkill->id_habilidad,
                        $tech['name'],
                        'tecnica',
                        $tech['links'] ?? []
                    );
                }
            }

            foreach ($soft as $s) {
                if (!empty($s['name'])) {
                    $insertedSkill = DB::selectOne(
                        'INSERT INTO "Habilidad" (
                            id_usuario,
                            nombre_habilidad,
                            tipo_habilidad,
                            nivel_dominio,
                            porcentaje_dominio,
                            estado
                        ) VALUES (?, ?, ?, ?, ?, ?)
                        RETURNING id_habilidad',
                        [
                            $idUsuario,
                            $s['name'],
                            'blanda',
                            $mapLevel($s['level'] ?? 'Intermedio'),
                            $resolveProgress($s['progress'], $s['level'] ?? 'Intermedio'),
                            'activo',
                        ]
                    );

                    $this->syncSkillLinks(
                        $idUsuario,
                        (int) $insertedSkill->id_habilidad,
                        $s['name'],
                        'blanda',
                        $s['links'] ?? []
                    );
                }
            }

            return response()->json([
                'message' => 'Habilidades sincronizadas exitosamente.'
            ], 200);
        });
    }

    private function syncSkillLinks(
        int $idUsuario,
        int $skillId,
        string $skillName,
        string $skillType,
        array $links
    ): void {
        foreach ($links as $link) {
            $referenceType = (string) ($link['referenceType'] ?? '');
            $referenceId = (int) ($link['referenceId'] ?? 0);
            $label = trim((string) ($link['label'] ?? ''));

            if ($label === '' || $referenceId <= 0) {
                throw ValidationException::withMessages([
                    'skills' => ['Cada vínculo debe apuntar a un proyecto, experiencia o certificación válidos.'],
                ]);
            }

            if ($skillType === 'tecnica' && ! in_array($referenceType, ['project', 'formation'], true)) {
                throw ValidationException::withMessages([
                    'skills' => ['Las habilidades técnicas solo pueden vincularse con proyectos o certificaciones.'],
                ]);
            }

            if ($skillType === 'blanda' && ! in_array($referenceType, ['experience', 'formation'], true)) {
                throw ValidationException::withMessages([
                    'skills' => ['Las habilidades blandas solo pueden vincularse con experiencias o certificaciones.'],
                ]);
            }

            $reference = $this->resolveReferenceForSkill($idUsuario, $skillName, $referenceType, $referenceId);

            if (! $reference) {
                $message = match ($referenceType) {
                    'project' => sprintf('El proyecto "%s" no usa %s.', $label, $skillName),
                    'experience' => sprintf('La experiencia "%s" no parece tratar de %s.', $label, $skillName),
                    default => sprintf('La certificación "%s" no parece respaldar %s.', $label, $skillName),
                };

                throw ValidationException::withMessages([
                    'skills' => [$message],
                ]);
            }

            DB::insert(
                'INSERT INTO "Habilidad_Vinculo" (
                    id_habilidad,
                    tipo_referencia,
                    id_proyecto,
                    id_experiencia,
                    id_formacion,
                    etiqueta_referencia
                ) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $skillId,
                    $this->mapReferenceType($referenceType),
                    $referenceType === 'project' ? $referenceId : null,
                    $referenceType === 'experience' ? $referenceId : null,
                    $referenceType === 'formation' ? $referenceId : null,
                    $reference->label,
                ]
            );
        }
    }

    private function resolveReferenceForSkill(
        int $idUsuario,
        string $skillName,
        string $referenceType,
        int $referenceId
    ): ?object {
        $normalizedSkill = mb_strtolower(trim($skillName), 'UTF-8');
        $searchLike = '%' . $normalizedSkill . '%';

        return match ($referenceType) {
            'project' => DB::selectOne(
                'SELECT p.id_proyecto AS id, p.nombre_proyecto AS label
                 FROM "Proyecto" p
                 INNER JOIN "Portafolio" pf ON pf.id_portafolio = p.id_portafolio
                 INNER JOIN "Tecnologia_Proyecto" tp ON tp.id_proyecto = p.id_proyecto
                 INNER JOIN "Tecnologia" t ON t.id_tecnologia = tp.id_tecnologia
                 WHERE p.id_proyecto = ?
                   AND pf.id_usuario = ?
                   AND LOWER(t.nombre_tecnologia) = ?',
                [$referenceId, $idUsuario, $normalizedSkill]
            ),
            'experience' => DB::selectOne(
                'SELECT id_experiencia AS id,
                        CONCAT(titulo_puesto, \' @ \', nombre_empresa) AS label
                 FROM "Experiencia_Laboral"
                 WHERE id_experiencia = ?
                   AND id_usuario = ?
                   AND LOWER(CONCAT_WS(\' \', titulo_puesto, nombre_empresa, COALESCE(descripcion_puesto, \'\'))) LIKE ?',
                [$referenceId, $idUsuario, $searchLike]
            ),
            'formation' => DB::selectOne(
                'SELECT id_formacion AS id,
                        carrera_especialidad AS label
                 FROM "Formacion_Academica"
                 WHERE id_formacion = ?
                   AND id_usuario = ?
                   AND LOWER(CONCAT_WS(\' \', carrera_especialidad, institucion, COALESCE(descripcion, \'\'))) LIKE ?',
                [$referenceId, $idUsuario, $searchLike]
            ),
            default => null,
        };
    }

    private function mapReferenceType(string $referenceType): string
    {
        return match ($referenceType) {
            'project' => 'proyecto',
            'experience' => 'experiencia',
            default => 'formacion',
        };
    }
}
