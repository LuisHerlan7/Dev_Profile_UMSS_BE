<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\RequestTimingLogger;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $frontendLoginUrl = rtrim((string) env('FRONTEND_URL', 'http://127.0.0.1:4200'), '/').'/login';

        // Permite que Laravel detecte correctamente HTTPS detrás de un reverse proxy.
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->expectsJson() || $request->is('api/*')
                ? null
                : $frontendLoginUrl
        );

        $middleware->append(RequestTimingLogger::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
