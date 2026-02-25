<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        array $extraMeta = [],
        int $status = 200,
        array $headers = []
    ): Response {
        if ($status === 204) {
            return response()->noContent(204, $headers);
        }

        $meta = self::buildMeta($extraMeta);

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => $meta ?: (object) [],
        ], $status, $headers);
    }

    public static function error(
        string $code,
        ?string $message = null,
        array $details = [],
        int $status = 400,
        array $headers = []
    ): Response {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message ?? $code,
                'details' => $details,
            ],
        ], $status, $headers);
    }

    public static function successPaginated(
        LengthAwarePaginator $paginator,
        mixed $data,
        array $extraMeta = [],
        int $status = 200,
        array $headers = []
    ): Response {
        $pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ];

        return self::success(
            $data,
            array_merge($extraMeta, ['pagination' => $pagination]),
            $status,
            $headers
        );
    }

    private static function buildMeta(array $extraMeta = []): array
    {
        $requestId = request()->header('X-Request-Id') ?? (string) Str::uuid();

        $baseMeta = [
            'request_id' => $requestId,
            'version' => config('api.version'),
            'timestamp' => now()->toIso8601String(),
        ];

        $baseMeta = array_filter($baseMeta, static fn ($v) => $v !== null);

        return array_merge($baseMeta, $extraMeta);
    }
}
