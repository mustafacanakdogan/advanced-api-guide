<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns same response for same idempotency key', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $payload = ['amount' => 120.50, 'currency' => 'usd'];
    $headers = ['Idempotency-Key' => 'demo-key-1234567890'];

    $store = [];
    Redis::shouldReceive('get')->andReturnUsing(function ($key) use (&$store) {
        return $store[$key] ?? null;
    });
    Redis::shouldReceive('set')->andReturnUsing(function ($key, $value) use (&$store) {
        $store[$key] = $value;
        return true;
    });
    Redis::shouldReceive('setex')->andReturnUsing(function ($key, $ttl, $value) use (&$store) {
        $store[$key] = $value;
        return true;
    });
    Redis::shouldReceive('del')->andReturnUsing(function ($key) use (&$store) {
        unset($store[$key]);
        return 1;
    });

    $first = $this->postJson('/api/v1/payments', $payload, $headers)->assertStatus(201);
    $second = $this->postJson('/api/v1/payments', $payload, $headers)->assertStatus(201);

    expect($first->json('data.transaction_id'))
        ->toBe($second->json('data.transaction_id'));
});
