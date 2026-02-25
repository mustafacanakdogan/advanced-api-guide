<?php

use App\Exceptions\GlobalExceptionHandler;
use App\Http\Middleware\ForceJsonRequest;
use App\Http\Middleware\MetricsMiddleware;
use App\Http\Middleware\RequestLoggingMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', ForceJsonRequest::class);
        $middleware->appendToGroup('api', MetricsMiddleware::class);
        $middleware->appendToGroup('api', RequestLoggingMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            return app(GlobalExceptionHandler::class)
                ->handle($e, $request);
        });
    })->create();
