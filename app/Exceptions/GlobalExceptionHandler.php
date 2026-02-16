<?php

namespace App\Exceptions;


use App\Enums\HttpErrorCodes;
use App\Http\Responses\GlobalExceptionResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;


class GlobalExceptionHandler
{
    public function handle(Throwable $e, Request $request): Response
    {
        $errorCode = $this->mapToErrorCode($e);
        $details   = $this->resolveDetails($e);
        $traceId   = $this->traceId($request);

        $this->logIfNecessary($e, $errorCode, $traceId);

        return (new GlobalExceptionResponse(
            code: $errorCode,
            message: config('app.debug') ? $e->getMessage() : null,
            details: $details,
            traceId: $traceId,
        ))->toResponse();
    }

    protected function mapToErrorCode(Throwable $e): HttpErrorCodes
    {
        if ($e instanceof ValidationException) {
            return HttpErrorCodes::VALIDATION_ERROR;
        }

        if ($e instanceof HttpExceptionInterface) {
            return match ($e->getStatusCode()) {
                401 => HttpErrorCodes::UNAUTHENTICATED,
                403 => HttpErrorCodes::UNAUTHORIZED,
                404 => HttpErrorCodes::NOT_FOUND,
                405 => HttpErrorCodes::METHOD_NOT_ALLOWED,
                429 => HttpErrorCodes::TOO_MANY_REQUESTS,
                default => HttpErrorCodes::INTERNAL_SERVER_ERROR,
            };
        }

        return HttpErrorCodes::INTERNAL_SERVER_ERROR;
    }

    protected function resolveDetails(Throwable $e): array
    {
        if ($e instanceof ValidationException) {
            return $e->errors();
        }

        return [];
    }

    protected function traceId(Request $request): string
    {
        return $request->header('X-Request-Id') ?? bin2hex(random_bytes(8));
    }

    protected function logIfNecessary(Throwable $e, HttpErrorCodes $code, string $traceId): void
    {
        Log::log($code->logLevel(), $e->getMessage(), [
            'trace_id' => $traceId,
            'exception' => get_class($e),
        ]);
    }
}
