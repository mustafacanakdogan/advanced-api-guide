<?php

use Illuminate\Support\Facades\Route;




Route::get('/ping', fn () => response()->json([
    'version' => 'v2',
    'pong' => true,
    'message' => 'pong v2',
])->header('X-API-Version', 'v2'));
