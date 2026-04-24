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
            'soft' => ['nullable', 'array'],
            'soft.*.name' => ['required', 'string', 'max:50'],
            'soft.*.level' => ['nullable', 'string', 'in:Principiante,Intermedio,Avanzado,Experto,Basico,basico,intermedio,avanzado,experto'],
            'soft.*.progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $technical = array_map(function (array $skill): array {
            return [
                'name' => trim((string) ($skill['name'] ?? '')),
                'level' => (string) ($skill['level'] ?? 'Intermedio'),
                'progress' => isset($skill['progress']) ? (int) $skill['progress'] : null,
            ];
        }, $payload['technical'] ?? []);

        $soft = array_map(function (array $skill): array {
            return [
                'name' => trim((string) ($skill['name'] ?? '')),
                'level' => (string) ($skill['level'] ?? 'Intermedio'),
                'progress' => isset($skill['progress']) ? (int) $skill['progress'] : null,
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

            $inserts = [];

            foreach ($technical as $tech) {
                if (!empty($tech['name'])) {
                    $inserts[] = [
                        'id_usuario' => $idUsuario,
                        'nombre_habilidad' => $tech['name'],
                        'tipo_habilidad' => 'tecnica',
                        'nivel_dominio' => $mapLevel($tech['level'] ?? 'Intermedio'),
                        'porcentaje_dominio' => $resolveProgress($tech['progress'], $tech['level'] ?? 'Intermedio'),
                        'estado' => 'activo'
                    ];
                }
            }

            foreach ($soft as $s) {
                if (!empty($s['name'])) {
                    $inserts[] = [
                        'id_usuario' => $idUsuario,
                        'nombre_habilidad' => $s['name'],
                        'tipo_habilidad' => 'blanda',
                        'nivel_dominio' => $mapLevel($s['level'] ?? 'Intermedio'),
                        'porcentaje_dominio' => $resolveProgress($s['progress'], $s['level'] ?? 'Intermedio'),
                        'estado' => 'activo'
                    ];
                }
            }

            if (count($inserts) > 0) {
                DB::table('Habilidad')->insert($inserts);
            }

            return response()->json([
                'message' => 'Habilidades sincronizadas exitosamente.'
            ], 200);
        });
    }
}
