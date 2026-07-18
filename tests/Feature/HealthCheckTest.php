<?php

declare(strict_types=1);

it('responde ok en el health check', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJson(['status' => 'ok']);
});
