<?php

use App\Http\Controllers\V2\AuthController;
use App\Http\Controllers\V2\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [AuthController::class, 'issueToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/revoke', [AuthController::class, 'revokeCurrent']);
    Route::post('/auth/revoke-all', [AuthController::class, 'revokeAll']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
});
