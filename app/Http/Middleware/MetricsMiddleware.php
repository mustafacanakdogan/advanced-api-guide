<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class MetricsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $this->increment('metrics:request_count');
        $this->increment('metrics:duration_total_ms', $durationMs);

        if ($response->getStatusCode() >= 400) {
            $this->increment('metrics:error_count');
        }

        return $response;
    }

    private function increment(string $key, int $by = 1): void
    {
        if (!Cache::has($key)) {
            Cache::put($key, 0);
        }

        Cache::increment($key, $by);
    }
}
