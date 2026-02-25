<?php

use App\Http\Controllers\V2\AuthController;
use App\Http\Controllers\V2\IdempotencyDemoController;
use App\Http\Controllers\V2\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IdempotencyMiddleware;

Route::post('/auth/token', [AuthController::class, 'issueToken'])
    ->middleware('throttle:auth-token');

Route::middleware(['auth:sanctum', 'throttle:auth-api'])->group(function () {
    Route::post('/auth/revoke', [AuthController::class, 'revokeCurrent']);
    Route::post('/auth/revoke-all', [AuthController::class, 'revokeAll']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);

    Route::post('/payments', [IdempotencyDemoController::class, 'store'])
        ->middleware(IdempotencyMiddleware::class);
});
