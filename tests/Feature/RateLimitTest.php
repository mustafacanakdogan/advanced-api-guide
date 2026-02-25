<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('rate limits auth token requests', function () {
    for ($i = 0; $i < 10; $i++) {
        $response = $this->postJson('/api/v1/auth/token', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        expect($response->getStatusCode())->not->toBe(429);
    }

    $this->postJson('/api/v1/auth/token', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

it('rate limits authenticated api requests', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    for ($i = 0; $i < 60; $i++) {
        $response = $this->getJson('/api/v1/users');
        expect($response->getStatusCode())->not->toBe(429);
    }

    $this->getJson('/api/v1/users')->assertStatus(429);
});
