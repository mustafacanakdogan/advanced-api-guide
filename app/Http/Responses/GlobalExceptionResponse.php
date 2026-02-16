<?php

namespace App\Http\Responses;

use App\Enums\HttpErrorCodes;
use Illuminate\Http\JsonResponse;

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
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $this->code->value,
                'message' => $this->message ?? $this->code->defaultMessage(),
                'details' => $this->details,
                'trace_id' => $this->traceId,
            ],
        ], $this->code->httpStatus());
    }
}
