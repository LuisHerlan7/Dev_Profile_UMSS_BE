<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panel resumido bajo /api/dashboard/developer (rama dev).
 * Si en dev ya existe una implementación completa, conserva esa versión al resolver conflictos.
 */
class DeveloperDashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Dashboard developer (dev branch)',
            'user_id' => $request->user()?->id,
        ]);
    }
}
