<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Rutas /api/admin/* (rama dev).
 */
class AdminDashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Admin dashboard summary (stub)']);
    }

    public function createAdmin(Request $request): JsonResponse
    {
        return response()->json(['message' => 'createAdmin (stub)'], 201);
    }
}
