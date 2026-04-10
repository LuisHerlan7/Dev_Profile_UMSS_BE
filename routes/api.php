<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\EvidenceModerationController;
use App\Http\Controllers\Dashboard\DeveloperDashboardController;
use App\Http\Controllers\Developer\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['github', 'linkedin']);
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['github', 'linkedin']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/dashboard', [AuthController::class, 'dashboard']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/dashboard/developer', [DeveloperDashboardController::class, 'show']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::post('/projects/{projectId}/evidences', [ProjectController::class, 'uploadEvidence']);
});

Route::prefix('admin')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/dashboard', [AdminDashboardController::class, 'summary']);
        Route::post('/users', [AdminDashboardController::class, 'createAdmin']);
        Route::get('/evidences', [EvidenceModerationController::class, 'index']);
        Route::patch('/evidences/{evidenceId}', [EvidenceModerationController::class, 'update']);
    });
