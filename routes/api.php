<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\EvidenceModerationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Dashboard\DeveloperDashboardController as DevBranchDashboardController;
use App\Http\Controllers\Developer\ProjectController;
use App\Http\Controllers\DeveloperDashboardController;
use App\Http\Controllers\DeveloperSettingsController;
use App\Http\Controllers\ExperienciaLaboralController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\FormacionAcademicaController;
use App\Http\Controllers\HabilidadController;
use App\Http\Controllers\ProyectoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth (Sanctum + OAuth) — backend-dev
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['github', 'linkedin']);
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['github', 'linkedin']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Dashboard / proyectos (estilo rama dev) — rutas bajo /api/...
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/dashboard/developer', [DevBranchDashboardController::class, 'show']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::post('/projects/{projectId}/evidences', [ProjectController::class, 'uploadEvidence']);
});

/*
|--------------------------------------------------------------------------
| Admin — rama dev
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/dashboard', [AdminDashboardController::class, 'summary']);
        Route::post('/users', [AdminDashboardController::class, 'createAdmin']);
        Route::get('/evidences', [EvidenceModerationController::class, 'index']);
        Route::patch('/evidences/{evidenceId}', [EvidenceModerationController::class, 'update']);
    });

/*
|--------------------------------------------------------------------------
| Developer (perfil, CV, archivos) — backend-dev
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('developer')->group(function (): void {
    Route::get('/dashboard', [DeveloperDashboardController::class, 'index']);

    Route::post('/experiencia', [ExperienciaLaboralController::class, 'store']);
    Route::delete('/experiencia/{id}', [ExperienciaLaboralController::class, 'destroy']);

    Route::post('/formacion', [FormacionAcademicaController::class, 'store']);
    Route::delete('/formacion/{id}', [FormacionAcademicaController::class, 'destroy']);

    Route::post('/habilidades/sync', [HabilidadController::class, 'sync']);

    Route::post('/settings/avatar', [DeveloperSettingsController::class, 'updateAvatar']);
    Route::post('/settings/profile', [DeveloperSettingsController::class, 'updateProfile']);
    Route::post('/settings/social-links', [DeveloperSettingsController::class, 'updateSocialLinks']);
    Route::post('/settings/email', [DeveloperSettingsController::class, 'updateEmail']);
    Route::post('/settings/password', [DeveloperSettingsController::class, 'updatePassword']);
    Route::post('/settings/verify-password', [DeveloperSettingsController::class, 'verifyPassword']);
    Route::post('/settings/highlights', [DeveloperSettingsController::class, 'syncHighlights']);

    Route::post('/proyecto', [ProyectoController::class, 'store']);
    Route::delete('/proyecto/{id}', [ProyectoController::class, 'destroy']);

    Route::prefix('files')->group(function (): void {
        //
    });
});

/*
|--------------------------------------------------------------------------
| Archivos públicos — backend-dev (coincide con URLs en dashboard / FE)
|--------------------------------------------------------------------------
*/
Route::prefix('developer/files')->group(function (): void {
    Route::get('/avatar/{id}', [FileDownloadController::class, 'getAvatar']);
    Route::get('/experiencia/{id}', [FileDownloadController::class, 'downloadExperiencia']);
    Route::get('/formacion/{id}', [FileDownloadController::class, 'downloadFormacion']);
    Route::get('/proyecto/{id}', [FileDownloadController::class, 'downloadProyecto']);
});
