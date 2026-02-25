<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns metrics payload', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/metrics');

    $response
        ->assertOk()
        ->assertJsonPath('requests_total', 0)
        ->assertJsonPath('errors_total', 0)
        ->assertJsonPath('avg_duration_ms', 0);
});
