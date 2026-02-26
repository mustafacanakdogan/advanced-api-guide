<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestLoggingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $requestId = $request->header('X-Request-Id') ?? (string) Str::uuid();
        $request->headers->set('X-Request-Id', $requestId);
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        Log::info('request.completed', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
        ]);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
