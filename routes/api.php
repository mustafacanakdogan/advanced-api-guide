<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(base_path('routes/version/v1.php'));
Route::prefix('v2')->group(base_path('routes/version/v2.php'));
