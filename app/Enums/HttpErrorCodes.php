<?php

namespace App\Enums;

enum HttpErrorCodes: string
{
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case UNAUTHENTICATED = 'UNAUTHENTICATED';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case NOT_FOUND = 'NOT_FOUND';
    case METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    case TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';
    case INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    case NOT_ACCEPTABLE = 'NOT_ACCEPTABLE';


    public function httpStatus(): int
    {
        return match ($this) {
            self::VALIDATION_ERROR => 422,
            self::UNAUTHENTICATED => 401,
            self::UNAUTHORIZED => 403,
            self::NOT_FOUND => 404,
            self::METHOD_NOT_ALLOWED => 405,
             self::NOT_ACCEPTABLE => 406,
            self::TOO_MANY_REQUESTS => 429,
            self::INTERNAL_SERVER_ERROR => 500,
        };
    }

    public function defaultMessage(): string
    {
        return match ($this) {
            self::VALIDATION_ERROR => 'The given data was invalid.',
            self::UNAUTHENTICATED => 'Authentication required.',
            self::UNAUTHORIZED => 'You are not allowed to perform this action.',
            self::NOT_FOUND => 'Resource not found.',
            self::METHOD_NOT_ALLOWED => 'Method not allowed.',
            self::TOO_MANY_REQUESTS => 'Too many requests.',
            self::INTERNAL_SERVER_ERROR => 'An unexpected error occurred.',
            self::NOT_ACCEPTABLE => 'Only application/json is supported.',
        };
    }

    public function logLevel(): string
    {
        return match ($this) {
            self::INTERNAL_SERVER_ERROR => 'error',
            self::VALIDATION_ERROR => 'warning',
            default => 'notice',
        };
    }
}
