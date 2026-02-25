<?php

use App\Enums\HttpErrorCodes;
use App\Exceptions\GlobalExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    $this->handler = new GlobalExceptionHandler();
    $this->request = Request::create('/test', 'GET');
});

it('handles validation exception correctly', function () {
    $validator = validator(['email' => 'invalid'], ['email' => 'required|email']);
    $exception = new ValidationException($validator);

    $response = $this->handler->handle($exception, $this->request);
    $content = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(422)
        ->and($content)->toHaveKey('success')
        ->and($content['success'])->toBeFalse()
        ->and($content)->toHaveKey('error')
        ->and($content['error'])->toHaveKey('code')
        ->and($content['error'])->toHaveKey('message')
        ->and($content['error'])->toHaveKey('details')
        ->and($content['error'])->toHaveKey('trace_id')
        ->and($content)->toHaveKey('meta');
});

it('handles 404 not found exception', function () {
    $exception = new NotFoundHttpException('Resource not found');

    $response = $this->handler->handle($exception, $this->request);

    expect($response->getStatusCode())->toBe(404);
});

it('handles 401 unauthenticated exception', function () {
    $exception = new HttpException(401, 'Unauthenticated');

    $response = $this->handler->handle($exception, $this->request);

    expect($response->getStatusCode())->toBe(401);
});

it('handles 403 unauthorized exception', function () {
    $exception = new HttpException(403, 'Forbidden');

    $response = $this->handler->handle($exception, $this->request);

    expect($response->getStatusCode())->toBe(403);
});

it('handles 429 too many requests exception', function () {
    $exception = new HttpException(429, 'Too Many Requests');

    $response = $this->handler->handle($exception, $this->request);

    expect($response->getStatusCode())->toBe(429);
});

it('handles generic exception as internal server error', function () {
    $exception = new Exception('Something went wrong');

    $response = $this->handler->handle($exception, $this->request);

    expect($response->getStatusCode())->toBe(500);
});

it('uses X-Request-Id header as trace id when present', function () {
    $requestId = 'test-trace-id-123';
    $request = Request::create('/test', 'GET', [], [], [], [
        'HTTP_X_REQUEST_ID' => $requestId
    ]);

    $exception = new Exception('Test');
    $response = $this->handler->handle($exception, $request);
    $content = json_decode($response->getContent(), true);

    expect($content['error']['trace_id'])->toBe($requestId);
});

it('generates trace id when X-Request-Id header is not present', function () {
    $exception = new Exception('Test');
    $response = $this->handler->handle($exception, $this->request);
    $content = json_decode($response->getContent(), true);

    expect($content['error']['trace_id'])
        ->toBeString()
        ->and(strlen($content['error']['trace_id']))->toBe(16);
});

it('logs exception with trace id', function () {
    Log::shouldReceive('log')
        ->once()
        ->withArgs(function ($level, $message, $context) {
            return isset($context['trace_id'])
                && isset($context['exception'])
                && $context['exception'] === Exception::class;
        });

    $exception = new Exception('Test exception');
    $this->handler->handle($exception, $this->request);
});

it('includes validation errors in details for validation exception', function () {
    $validator = validator(
        ['email' => 'invalid', 'name' => ''],
        ['email' => 'required|email', 'name' => 'required']
    );
    $exception = new ValidationException($validator);

    $response = $this->handler->handle($exception, $this->request);
    $content = json_decode($response->getContent(), true);

    expect($content['error']['details'])
        ->toBeArray()
        ->toHaveKey('fields')
        ->and($content['error']['details']['fields'])
        ->toHaveKey('email')
        ->toHaveKey('name');
});

it('returns empty details for non-validation exceptions', function () {
    $exception = new Exception('Test');
    $response = $this->handler->handle($exception, $this->request);
    $content = json_decode($response->getContent(), true);

    expect($content['error']['details'])->toBeArray()->toBeEmpty();
});

it('includes exception message when debug mode is enabled', function () {
    config(['app.debug' => true]);

    $message = 'Detailed error message';
    $exception = new Exception($message);
    $response = $this->handler->handle($exception, $this->request);
    $content = json_decode($response->getContent(), true);

    expect($content['error']['message'])->toBe($message);
});

it('hides exception message when debug mode is disabled', function () {
    config(['app.debug' => false]);

    $exception = new Exception('Detailed error message');
    $response = $this->handler->handle($exception, $this->request);
    $content = json_decode($response->getContent(), true);


    expect($content['error']['message'])
        ->toBeString()
        ->not->toBe('Detailed error message');
});

it('handles 405 method not allowed exception', function () {
    $exception = new HttpException(405, 'Method Not Allowed');

    $response = $this->handler->handle($exception, $this->request);

    expect($response->getStatusCode())->toBe(405);
});

it('response structure is correct', function () {
    $exception = new Exception('Test');
    $response = $this->handler->handle($exception, $this->request);
    $content = json_decode($response->getContent(), true);

    expect($content)
        ->toHaveKey('success')
        ->toHaveKey('error')
        ->and($content['success'])->toBeFalse()
        ->and($content['error'])->toBeArray()
        ->and($content['error'])->toHaveKeys(['code', 'message', 'details', 'trace_id'])
        ->and($content)->toHaveKey('meta');
});

it('maps correct error codes for http exceptions', function () {
    $cases = [
        [401, HttpErrorCodes::UNAUTHENTICATED],
        [403, HttpErrorCodes::UNAUTHORIZED],
        [404, HttpErrorCodes::NOT_FOUND],
        [405, HttpErrorCodes::METHOD_NOT_ALLOWED],
        [429, HttpErrorCodes::TOO_MANY_REQUESTS],
    ];

    foreach ($cases as [$statusCode, $expectedCode]) {
        $exception = new HttpException($statusCode);
        $response = $this->handler->handle($exception, $this->request);
        $content = json_decode($response->getContent(), true);

        expect($content['error']['code'])->toBe($expectedCode->value);
    }
});

it('returns json response with correct content type', function () {
    $exception = new Exception('Test');
    $response = $this->handler->handle($exception, $this->request);

    expect($response->headers->get('Content-Type'))
        ->toContain('application/json');
});

it('validation exception includes all error messages', function () {
    $validator = validator(
        ['email' => 'not-an-email', 'password' => '123'],
        [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'name' => 'required'
        ]
    );

    $exception = new ValidationException($validator);
    $response = $this->handler->handle($exception, $this->request);
    $content = json_decode($response->getContent(), true);

    expect($content['error']['details'])
        ->toHaveKey('fields')
        ->and($content['error']['details']['fields'])
        ->toHaveKey('email')
        ->toHaveKey('password')
        ->toHaveKey('name')
        ->and($content['error']['details']['fields']['email'])->toBeArray()
        ->and($content['error']['details']['fields']['password'])->toBeArray()
        ->and($content['error']['details']['fields']['name'])->toBeArray();
});
