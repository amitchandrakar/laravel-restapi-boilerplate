<?php

declare(strict_types=1);
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

it('wraps login validation errors in the standard API envelope', function () {
    $response = $this->postJson('/api/v1/app/auth/login', []);

    $response
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('statusCode', 422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure([
            'meta' => ['timestamp', 'requestId', 'version'],
            'error' => ['fields'],
        ]);

    $fields = $response->json('error.fields');
    expect($fields)->toBeArray();
    expect($fields)->not->toBeEmpty();
    expect($fields[0])->toHaveKey('field');
    expect($fields[0])->toHaveKey('message');
});
