<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()->paginate(10);

        return ApiResponse::successPaginated(
            $users,
            UserResource::collection($users)->resolve()
        );
    }

    public function show(User $user)
    {
        return ApiResponse::success(
            UserResource::make($user)->resolve()
        );
    }



}
