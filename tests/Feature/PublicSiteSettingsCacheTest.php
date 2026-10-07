<?php

declare(strict_types=1);

use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);
});

it('serves public site settings from cache after the first request', function (): void {
    DB::enableQueryLog();
    $this->getJson('/api/v1/app/public/site-settings')->assertStatus(200);
    $coldSiteSettingsQueries = collect(DB::getQueryLog())
        ->filter(static fn(array $entry): bool => str_contains((string) ($entry['query'] ?? ''), 'site_settings'))
        ->count();

    DB::flushQueryLog();

    $this->getJson('/api/v1/app/public/site-settings')->assertStatus(200)->assertJsonPath('success', true);

    $warmSiteSettingsQueries = collect(DB::getQueryLog())
        ->filter(static fn(array $entry): bool => str_contains((string) ($entry['query'] ?? ''), 'site_settings'))
        ->count();

    expect($warmSiteSettingsQueries)->toBeLessThan($coldSiteSettingsQueries);
});

it('invalidates public site settings cache when admin updates site settings', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-cache-' . uniqid('', true) . '@example.com');

    $this->getJson('/api/v1/app/public/site-settings')->assertStatus(200)->assertJsonPath('success', true);

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/settings/site', [
            'siteName' => 'Cached Update Name',
            'maintenanceMode' => false,
        ])
        ->assertStatus(200);

    $this->getJson('/api/v1/app/public/site-settings')
        ->assertStatus(200)
        ->assertJsonPath('data.siteName', 'Cached Update Name');
});
