<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class MetricsController extends Controller
{
    public function __invoke(): Response
    {
        $requests = (int) Cache::get('metrics:request_count', 0);
        $errors = (int) Cache::get('metrics:error_count', 0);
        $durationTotal = (int) Cache::get('metrics:duration_total_ms', 0);

        $avgDuration = $requests > 0 ? (int) round($durationTotal / $requests) : 0;

        return ApiResponse::success([
            'requests_total' => $requests,
            'errors_total' => $errors,
            'avg_duration_ms' => $avgDuration,
        ]);
       
    }
}
