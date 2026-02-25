<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;

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

    public function cursor(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min($limit, 100));

        $users = User::query()
            ->orderByDesc('id')
            ->cursorPaginate($limit)
            ->withQueryString();

        $next = $users->nextCursor()?->encode();
        $prev = $users->previousCursor()?->encode();

        return ApiResponse::success(
            UserResource::collection($users)->resolve(),
            [
                'cursor' => [
                    'next' => $next,
                    'prev' => $prev,
                ],
            ]
        );
    }

    public function show(User $user)
    {
        return ApiResponse::success(
            UserResource::make($user)->resolve()
        );
    }



}
