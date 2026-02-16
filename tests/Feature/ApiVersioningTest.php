<?php

it('returns v1 ping response with version header', function () {
    $response = $this->getJson('/api/v1/ping');

    $response->assertOk()
        ->assertHeader('X-API-Version', 'v1')
        ->assertJson([
            'version' => 'v1',
            'pong' => true,
        ]);
});

it('returns v2 ping response with version header', function () {
    $response = $this->getJson('/api/v2/ping');

    $response->assertOk()
        ->assertHeader('X-API-Version', 'v2')
        ->assertJson([
            'version' => 'v2',
            'pong' => true,
            'message' => 'pong v2',
        ]);
});
