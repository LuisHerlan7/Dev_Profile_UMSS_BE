<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeveloperReportController extends Controller
{
    public function __construct(
        private readonly GeneradorUsuarioSync $generadorUsuarioSync
    ) {}

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== 'desarrollador') {
            return response()->json([
                'message' => 'Solo desarrolladores pueden generar reportes.',
            ], 403);
        }

        $data = $request->validate([
            'format' => ['required', 'in:pdf,word'],
            'name' => ['required', 'string', 'max:200'],
        ]);

        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);
        $portfolio = DB::selectOne('SELECT id_portafolio FROM "Portafolio" WHERE id_usuario = ? LIMIT 1', [$idUsuario]);

        if (! $portfolio) {
            return response()->json([
                'message' => 'No se encontró el portafolio del desarrollador.',
            ], 404);
        }

        $missingRequirements = $this->missingPortfolioRequirements($idUsuario, (int) $portfolio->id_portafolio);

        if ($missingRequirements !== []) {
            return response()->json([
                'message' => 'Completa los datos minimos del portafolio antes de exportarlo.',
                'missing_requirements' => $missingRequirements,
            ], 422);
        }

        $report = DB::selectOne(
            'INSERT INTO "Reporte" (
                id_portafolio,
                tipo_reporte,
                nombre_reporte,
                contenido,
                formato,
                fecha_generacion,
                fecha_descarga_ultima,
                total_descargas
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING id_reporte, nombre_reporte, formato, fecha_generacion',
            [
                $portfolio->id_portafolio,
                'general',
                $data['name'],
                null,
                $data['format'] === 'word' ? 'docx' : 'pdf',
                now(),
                now(),
                1,
            ]
        );

        return response()->json([
            'message' => 'Reporte generado correctamente.',
            'report' => $report,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function missingPortfolioRequirements(int $idUsuario, int $idPortafolio): array
    {
        $missing = [];
        $usuario = DB::table('Usuario')->where('id_usuario', $idUsuario)->first();

        if (! $usuario || ! filled($usuario->biografia ?? null)) {
            $missing[] = 'biografia';
        }

        if (DB::table('Proyecto')->where('id_portafolio', $idPortafolio)->count() === 0) {
            $missing[] = 'proyectos';
        }

        if (DB::table('Habilidad')->where('id_usuario', $idUsuario)->count() === 0) {
            $missing[] = 'habilidades';
        }

        $hasExperience = DB::table('Experiencia_Laboral')->where('id_usuario', $idUsuario)->exists();
        $hasEducation = DB::table('Formacion_Academica')->where('id_usuario', $idUsuario)->exists();

        if (! $hasExperience && ! $hasEducation) {
            $missing[] = 'experiencia_o_formacion';
        }

        return $missing;
    }
}
