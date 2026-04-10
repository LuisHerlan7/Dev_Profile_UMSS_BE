<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->prefix('developer')->group(function (): void {
    Route::get('/dashboard', [\App\Http\Controllers\DeveloperDashboardController::class, 'index']);
    
    // Experiencia y Formación
    Route::post('/experiencia', [\App\Http\Controllers\ExperienciaLaboralController::class, 'store']);
    Route::delete('/experiencia/{id}', [\App\Http\Controllers\ExperienciaLaboralController::class, 'destroy']);
    
    Route::post('/formacion', [\App\Http\Controllers\FormacionAcademicaController::class, 'store']);
    Route::delete('/formacion/{id}', [\App\Http\Controllers\FormacionAcademicaController::class, 'destroy']);
    
    // Habilidades
    Route::post('/habilidades/sync', [\App\Http\Controllers\HabilidadController::class, 'sync']);
    
    // Ajustes y Perfil
    Route::post('/settings/avatar', [\App\Http\Controllers\DeveloperSettingsController::class, 'updateAvatar']);
    Route::post('/settings/profile', [\App\Http\Controllers\DeveloperSettingsController::class, 'updateProfile']);
    
    // Descarga de Archivos (Públicas o semi-públicas según lógica de controller)
    Route::prefix('files')->group(function (): void {
        Route::get('/experiencia/{id}', [\App\Http\Controllers\FileDownloadController::class, 'downloadExperiencia']);
        Route::get('/formacion/{id}', [\App\Http\Controllers\FileDownloadController::class, 'downloadFormacion']);
        Route::get('/proyecto/{id}', [\App\Http\Controllers\FileDownloadController::class, 'downloadProyecto']);
        Route::get('/avatar/{id}', [\App\Http\Controllers\FileDownloadController::class, 'getAvatar']);
    });
});
