<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;

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

        $technical = $request->input('technical', []);
        $soft = $request->input('soft', []);

        $mapLevel = function($level) {
            $l = strtolower($level);
            if ($l === 'principiante' || $l === 'basico') return 'basico';
            if ($l === 'avanzado') return 'avanzado';
            if ($l === 'experto') return 'experto';
            return 'intermedio';
        };

        return DB::transaction(function () use ($idUsuario, $technical, $soft, $mapLevel) {
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
                        'nivel_dominio' => null,
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
