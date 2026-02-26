<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonRequest
{

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->expectsJson()) {
            return ApiResponse::error(
                code: 'NOT_ACCEPTABLE',
                message: 'Only application/json is supported.',
                status: 406
            );
           
        }

        return $next($request);
    }
}
