<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvidenceModerationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function update(Request $request, string $evidenceId): JsonResponse
    {
        return response()->json([
            'message' => 'Evidence updated (stub)',
            'evidence_id' => $evidenceId,
        ]);
    }
}
