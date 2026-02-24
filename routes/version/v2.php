<?php

use App\Http\Controllers\V2\UserController;
use Illuminate\Support\Facades\Route;





Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{user}', [UserController::class, 'show']);
