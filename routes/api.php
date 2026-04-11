<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
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
| Auth (Sanctum + OAuth)
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
| Developer (autenticado)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('developer')->group(function (): void {
    Route::get('/dashboard', [DeveloperDashboardController::class, 'index']);

    // Experiencia y formación
    Route::post('/experiencia', [ExperienciaLaboralController::class, 'store']);
    Route::delete('/experiencia/{id}', [ExperienciaLaboralController::class, 'destroy']);

    Route::post('/formacion', [FormacionAcademicaController::class, 'store']);
    Route::delete('/formacion/{id}', [FormacionAcademicaController::class, 'destroy']);

    // Habilidades
    Route::post('/habilidades/sync', [HabilidadController::class, 'sync']);

    // Ajustes y perfil
    Route::post('/settings/avatar', [DeveloperSettingsController::class, 'updateAvatar']);
    Route::post('/settings/profile', [DeveloperSettingsController::class, 'updateProfile']);
    Route::post('/settings/social-links', [DeveloperSettingsController::class, 'updateSocialLinks']);
    Route::post('/settings/email', [DeveloperSettingsController::class, 'updateEmail']);
    Route::post('/settings/password', [DeveloperSettingsController::class, 'updatePassword']);
    Route::post('/settings/verify-password', [DeveloperSettingsController::class, 'verifyPassword']);
    Route::post('/settings/highlights', [DeveloperSettingsController::class, 'syncHighlights']);

    // Proyectos
    Route::post('/proyecto', [ProyectoController::class, 'store']);
    Route::delete('/proyecto/{id}', [ProyectoController::class, 'destroy']);

    // Descargas protegidas (reservado; hoy vacío)
    Route::prefix('files')->group(function (): void {
        //
    });
});

/*
|--------------------------------------------------------------------------
| Archivos públicos (<img>, window.open sin Bearer)
| Prefijo developer/files (no developer-files): coincide con dashboard y FE.
|--------------------------------------------------------------------------
*/
Route::prefix('developer/files')->group(function (): void {
    Route::get('/avatar/{id}', [FileDownloadController::class, 'getAvatar']);
    Route::get('/experiencia/{id}', [FileDownloadController::class, 'downloadExperiencia']);
    Route::get('/formacion/{id}', [FileDownloadController::class, 'downloadFormacion']);
    Route::get('/proyecto/{id}', [FileDownloadController::class, 'downloadProyecto']);
});
