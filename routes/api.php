<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:auth-api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::prefix('v1')->group(base_path('routes/version/v1.php'));
Route::prefix('v2')->group(base_path('routes/version/v2.php'));
