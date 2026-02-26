<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthTokenRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function issueToken(AuthTokenRequest $request)
    {
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return ApiResponse::error(
                code: 'INVALID_CREDENTIALS',
                message: 'Email or password is incorrect.',
                status: 401
            );
        }

        $token = $user->createToken('api');

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function revokeCurrent(Request $request)
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return ApiResponse::success(['revoked' => true]);
    }

    public function revokeAll(Request $request)
    {
        $request->user()?->tokens()->delete();

        return ApiResponse::success(['revoked' => true]);
    }
}
