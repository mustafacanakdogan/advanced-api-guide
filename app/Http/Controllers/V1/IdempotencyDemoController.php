<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Str;

class IdempotencyDemoController extends Controller
{
    public function store(PaymentRequest $request)
    {
        $validated = $request->validated();

        return ApiResponse::success([
            'transaction_id' => (string) Str::uuid(),
            'amount' => $validated['amount'],
            'currency' => strtoupper($validated['currency']),
        ], status: 201);
    }
}
