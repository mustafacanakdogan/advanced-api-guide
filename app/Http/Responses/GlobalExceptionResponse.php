<?php

namespace App\Http\Responses;

use App\Enums\HttpErrorCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class GlobalExceptionResponse
{
    public function __construct(
        protected HttpErrorCodes $code,
        protected ?string $message = null,
        protected array $details = [],
        protected ?string $traceId = null,
    ) {}

    public function toResponse(): JsonResponse
    {
        $requestId = request()->attributes->get('request_id')
            ?? request()->header('X-Request-Id')
            ?? (string) Str::uuid();

        return response()->json([
            'success' => false,
            'error' => [
                'code' => $this->code->value,
                'message' => $this->message ?? $this->code->defaultMessage(),
                'details' => $this->details,
                'trace_id' => $this->traceId,
            ],
            'meta' => [
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ],
        ], $this->code->httpStatus());
    }
}
