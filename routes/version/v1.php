<?php

use Illuminate\Support\Facades\Route;


Route::get('/ping', fn () => response()->json([
    'version' => 'v1',
    'pong' => true,
])->header('X-API-Version', 'v1'));
