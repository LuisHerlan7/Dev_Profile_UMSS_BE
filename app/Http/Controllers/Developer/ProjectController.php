<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Proyectos bajo /api/projects (rama dev).
 * Convive con ProyectoController en /api/developer/proyecto (backend-dev).
 */
class ProjectController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Project created (stub)', 'data' => $request->all()], 201);
    }

    public function uploadEvidence(Request $request, string $projectId): JsonResponse
    {
        return response()->json([
            'message' => 'Evidence upload (stub)',
            'project_id' => $projectId,
        ], 201);
    }
}
