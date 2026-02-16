<?php

use App\Http\Middleware\IdempotencyMiddleware;
use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Route::post('/api/orders', function () {
        return response()->json(['ok' => true], 201);
    })->middleware(IdempotencyMiddleware::class);
});

it('caches successful json response and serves subsequent request from cache', function () {
    $user = User::factory()->create();

    $key = 'abcDEF123_-abcDEF123_-';
    $endpoint = 'api/orders';
    $method = 'POST';
    $base = "idem:{$user->id}:{$method}:{$endpoint}:{$key}";
    $respKey = "{$base}:resp";
    $lockKey = "{$base}:lock";

    $cachedPayload = json_encode([
        'data' => ['ok' => true],
        'status' => 201,
        'headers' => ['Content-Type' => 'application/json'],
    ]);

    Redis::shouldReceive('get')
        ->once()
        ->with($respKey)
        ->andReturn(null);

    Redis::shouldReceive('set')
        ->once()
        ->with($lockKey, 'processing', 'EX', 60, 'NX')
        ->andReturn(true);

    Redis::shouldReceive('setex')
        ->once()
        ->withArgs(function ($keyArg, $ttlArg, $valueArg) use ($respKey) {
            if ($keyArg !== $respKey) return false;
            if ($ttlArg !== 86400) return false;

            $decoded = json_decode($valueArg, true);
            return is_array($decoded)
                && ($decoded['data']['ok'] ?? null) === true
                && ($decoded['status'] ?? null) === 201;
        })
        ->andReturn(true);

    Redis::shouldReceive('del')
        ->once()
        ->with($lockKey)
        ->andReturn(1);

    $first = $this->actingAs($user)
        ->withHeader('Idempotency-Key', $key)
        ->postJson('/api/orders');

    $first->assertStatus(201)
        ->assertJson(['ok' => true]);

    Redis::shouldReceive('get')
        ->once()
        ->with($respKey)
        ->andReturn($cachedPayload);

    $second = $this->actingAs($user)
        ->withHeader('Idempotency-Key', $key)
        ->postJson('/api/orders');

    $second->assertStatus(201)
        ->assertHeader('X-Idempotent', 'true')
        ->assertHeader('X-Cache-Hit', 'true')
        ->assertJson(['ok' => true]);
});

it('returns 409 when request is already processing', function () {
    $user = User::factory()->create();

    $key = 'abcDEF123_-abcDEF123_-';
    $endpoint = 'api/orders';
    $method = 'POST';
    $base = "idem:{$user->id}:{$method}:{$endpoint}:{$key}";
    $respKey = "{$base}:resp";
    $lockKey = "{$base}:lock";

    Redis::shouldReceive('get')
        ->once()
        ->with($respKey)
        ->andReturn(null);

    Redis::shouldReceive('set')
        ->once()
        ->with($lockKey, 'processing', 'EX', 60, 'NX')
        ->andReturn(false);

    $res = $this->actingAs($user)
        ->withHeader('Idempotency-Key', $key)
        ->postJson('/api/orders');

    $res->assertStatus(409)
        ->assertJson(['message' => 'Request already processing']);
});

it('returns 400 for invalid idempotency key and does not hit redis', function () {
    $user = User::factory()->create();

    Redis::shouldReceive('get')->never();
    Redis::shouldReceive('set')->never();
    Redis::shouldReceive('setex')->never();
    Redis::shouldReceive('del')->never();

    $res = $this->actingAs($user)
        ->withHeader('Idempotency-Key', 'bad key!!')
        ->postJson('/api/orders');

    $res->assertStatus(400)
        ->assertJson(['message' => 'Invalid Idempotency-Key format']);
});
