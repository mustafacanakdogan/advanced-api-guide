<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    private const LOCK_TTL = 60;
    private const RESPONSE_TTL = 86400;
    private const LOCK_VALUE = 'processing';

    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        $userId = $request->user()?->id;
        if (!$userId) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');
        if (!$key) {
            return $next($request);
        }

        if (!$this->isValidKey($key)) {
            return ApiResponse::error(
                code: 'invalid_idempotency_key',
                message: 'Invalid Idempotency-Key format',
                status: 400
            );
        }

        $base = $this->baseKey($userId, $request, $key);
        $respKey = "{$base}:resp";
        $lockKey = "{$base}:lock";

        $cached = Redis::get($respKey);
        if ($cached) {
            $payload = json_decode($cached, true);

            return response()
                ->json($payload['data'], $payload['status'])
                ->withHeaders($payload['headers'] ?? [])
                ->header('X-Idempotent', 'true')
                ->header('X-Cache-Hit', 'true');
        }

        $locked = Redis::set($lockKey, self::LOCK_VALUE, 'EX', self::LOCK_TTL, 'NX');
        if (!$locked) {
            return ApiResponse::error(
                code: 'request_in_progress',
                message: 'Request with this Idempotency-Key is already being processed.',
                status: 409
            );
        }

        try {
            $response = $next($request);

            if ($this->shouldCache($response)) {
                $this->cacheResponse($respKey, $response);
            }

            Redis::del($lockKey);

            return $response;

        } catch (\Throwable $e) {
            Redis::del($lockKey);
            throw $e;
        }
    }

    private function isValidKey(string $key): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9\-_]{16,128}$/', $key);
    }

    private function baseKey(int|string $userId, Request $request, string $key): string
    {
        $endpoint = $request->route()?->uri() ?? $request->path();
        $method = strtoupper($request->method());

        return "idem:{$userId}:{$method}:{$endpoint}:{$key}";
    }

    private function shouldCache(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return $response->isSuccessful()
            && str_contains($contentType, 'application/json');
    }

    private function cacheResponse(string $respKey, Response $response): void
    {
        $contentType = $response->headers->get('Content-Type', 'application/json');
        $data = json_decode($response->getContent(), true);

        Redis::setex($respKey, self::RESPONSE_TTL, json_encode([
            'data' => $data,
            'status' => $response->getStatusCode(),
            'headers' => [
                'Content-Type' => $contentType,
            ],
        ]));
    }
}
