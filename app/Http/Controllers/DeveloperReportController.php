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
            'content_html' => ['nullable', 'string', 'max:250000'],
        ]);

        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);
        $portfolio = DB::selectOne('SELECT id_portafolio FROM "Portafolio" WHERE id_usuario = ? LIMIT 1', [$idUsuario]);

        if (! $portfolio) {
            return response()->json([
                'message' => 'No se encontró el portafolio del desarrollador.',
            ], 404);
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
                filled($data['content_html'] ?? null) ? $data['content_html'] : null,
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
}
