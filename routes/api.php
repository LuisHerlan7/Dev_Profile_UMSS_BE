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

Route::middleware('auth:sanctum')->prefix('developer')->group(function (): void {
    Route::get('/dashboard', [\App\Http\Controllers\DeveloperDashboardController::class, 'index']);

    Route::post('/experiencia', [\App\Http\Controllers\ExperienciaLaboralController::class, 'store']);
    Route::put('/experiencia/{id}', [\App\Http\Controllers\ExperienciaLaboralController::class, 'update']);
    Route::delete('/experiencia/{id}', [\App\Http\Controllers\ExperienciaLaboralController::class, 'destroy']);

    Route::post('/formacion', [\App\Http\Controllers\FormacionAcademicaController::class, 'store']);
    Route::put('/formacion/{id}', [\App\Http\Controllers\FormacionAcademicaController::class, 'update']);
    Route::delete('/formacion/{id}', [\App\Http\Controllers\FormacionAcademicaController::class, 'destroy']);

    Route::post('/habilidades/sync', [\App\Http\Controllers\HabilidadController::class, 'sync']);

    Route::post('/settings/avatar', [\App\Http\Controllers\DeveloperSettingsController::class, 'updateAvatar']);
    Route::post('/settings/profile', [\App\Http\Controllers\DeveloperSettingsController::class, 'updateProfile']);
    Route::post('/settings/social-links', [\App\Http\Controllers\DeveloperSettingsController::class, 'updateSocialLinks']);
    Route::post('/settings/email', [\App\Http\Controllers\DeveloperSettingsController::class, 'updateEmail']);
    Route::post('/settings/password', [\App\Http\Controllers\DeveloperSettingsController::class, 'updatePassword']);
    Route::post('/settings/verify-password', [\App\Http\Controllers\DeveloperSettingsController::class, 'verifyPassword']);
    Route::post('/settings/highlights', [\App\Http\Controllers\DeveloperSettingsController::class, 'syncHighlights']);
    Route::post('/settings/visibility', [\App\Http\Controllers\DeveloperSettingsController::class, 'updateVisibility']);

    Route::post('/proyecto', [\App\Http\Controllers\ProyectoController::class, 'store']);
    Route::get('/proyecto/{id}', [\App\Http\Controllers\ProyectoController::class, 'show']);
    Route::post('/proyecto/{id}', [\App\Http\Controllers\ProyectoController::class, 'update']);
    Route::patch('/proyecto/{id}/visibility', [\App\Http\Controllers\ProyectoController::class, 'updateVisibility']);
    Route::delete('/proyecto/{id}', [\App\Http\Controllers\ProyectoController::class, 'destroy']);
    Route::put('/evidencias/{id}', [\App\Http\Controllers\ProyectoController::class, 'updateEvidence']);

    Route::prefix('files')->group(function (): void {
    });
});

Route::get('/portafolios', [\App\Http\Controllers\PublicProfileController::class, 'index']);
Route::get('/portafolios/{id}', [\App\Http\Controllers\PublicProfileController::class, 'show']);

Route::prefix('developer/files')->group(function (): void {
    Route::get('/avatar/{id}', [\App\Http\Controllers\FileDownloadController::class, 'getAvatar']);
    Route::get('/experiencia/{id}', [\App\Http\Controllers\FileDownloadController::class, 'downloadExperiencia']);
    Route::get('/formacion/{id}', [\App\Http\Controllers\FileDownloadController::class, 'downloadFormacion']);
    Route::get('/proyecto/{id}', [\App\Http\Controllers\FileDownloadController::class, 'downloadProyecto']);
    Route::get('/evidencia/{id}', [\App\Http\Controllers\FileDownloadController::class, 'downloadEvidencia']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/dashboard/developer', [\App\Http\Controllers\DeveloperDashboardController::class, 'index']);
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
