<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestTimingLogger
{
    /**
     * @param \Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $elapsedMs = (int) round((microtime(true) - $start) * 1000);
        $timestamp = now()->format('Y-m-d H:i:s');
        $path = '/'.$request->path();

        Log::info(sprintf('%s | %s ~ %dms', $timestamp, $path, $elapsedMs));

        return $response;
    }
}

