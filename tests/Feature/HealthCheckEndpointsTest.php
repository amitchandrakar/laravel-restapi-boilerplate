<?php

declare(strict_types=1);

it('returns public health status using the standard envelope', function () {
    $this->getJson('/api/health')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => ['status', 'timestamp', 'services' => ['database', 'cache']],
        ]);
});

it('lists extended dependencies on the detailed health endpoint', function () {
    $this->getJson('/api/health/detailed')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'services' => ['database', 'cache', 'queue', 'storage', 'object_storage', 'search'],
            ],
        ]);
});
