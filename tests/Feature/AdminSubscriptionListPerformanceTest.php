<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

it('lists expired subscriptions with a bounded query count', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-sub-perf-' . uniqid('', true) . '@example.com');
    $package = Package::query()->first();
    expect($package)->not->toBeNull();

    $candidateRoleId = (int) DB::table('roles')->where('name', 'candidate')->value('id');

    for ($i = 0; $i < 10; $i++) {
        $user = User::query()->create([
            'first_name' => 'Sub',
            'last_name' => 'User' . $i,
            'email' => 'sub-perf-' . $i . '-' . uniqid('', true) . '@example.com',
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => $candidateRoleId,
        ]);
        $user->assignRole('candidate');

        Subscription::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'package_id' => $package->id,
            'subscription_status' => 'expired',
            'started_at' => now()->subYear(),
            'ends_at' => now()->subMonth(),
        ]);
    }

    DB::enableQueryLog();
    DB::flushQueryLog();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/subscriptions/expired?page=1&perPage=15')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(9);
});
