<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class SlowRequestDemoController extends Controller
{
    public function __invoke(Request $request)
    {
        $ms = (int) $request->query('sleep_ms', 1200);
        $ms = max(0, min($ms, 5000));

        usleep($ms * 1000);

        return ApiResponse::success([
            'slept_ms' => $ms,
        ]);
    }
}
