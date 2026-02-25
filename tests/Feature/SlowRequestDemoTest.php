<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns slow request demo payload', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/slow?sleep_ms=10');

    $response
        ->assertOk()
        ->assertJsonPath('data.slept_ms', 10);
});
