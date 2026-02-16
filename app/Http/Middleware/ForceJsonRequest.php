<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonRequest
{

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Only application/json is supported.',
                'code' => 406
            ], 406);
        }

        return $next($request);
    }
}
