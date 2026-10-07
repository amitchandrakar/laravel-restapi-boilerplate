<?php

declare(strict_types=1);

use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('lists admin candidates with a bounded query count', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-perf-' . uniqid('', true) . '@example.com');

    for ($i = 0; $i < 15; $i++) {
        $candidate = $this->createUserWithRole('candidate', 'perf-' . $i . '-' . uniqid('', true) . '@example.com');
        $candidate->update([
            'profile_status' => 'published',
            'published_at' => now(),
            'current_city' => 'Raipur',
            'sub_caste' => 'Chandrakar',
        ]);
    }

    DB::enableQueryLog();
    DB::flushQueryLog();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/candidates?bucket=published&perPage=15')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(10);
});
