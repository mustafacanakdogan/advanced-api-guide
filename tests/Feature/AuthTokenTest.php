<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('issues token with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $response = $this->postJson('/api/v1/auth/token', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure([
            'success',
            'data' => ['token', 'token_type'],
            'meta' => ['request_id', 'timestamp'],
        ]);
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $response = $this->postJson('/api/v1/auth/token', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ]);

    $response
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
});

it('returns validation error format for missing credentials', function () {
    $response = $this->postJson('/api/v1/auth/token', []);

    $response
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.details.fields.email.0', 'The email field is required.')
        ->assertJsonPath('error.details.fields.password.0', 'The password field is required.');
});
