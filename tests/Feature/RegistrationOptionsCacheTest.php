<?php

declare(strict_types=1);

use App\Models\Package;
use App\Services\PackageService;
use App\Support\CacheKeys;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Cache;

it('caches registration options on repeat requests', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);
    $this->seed(PackageCatalogSeeder::class);

    Cache::forget(CacheKeys::registrationOptions());

    $first = $this->getJson('/api/v1/app/auth/registration')->assertStatus(200)->assertJsonPath('success', true);

    expect(Cache::has(CacheKeys::registrationOptions()))->toBeTrue();

    $firstPackageName = $first->json('data.packages.0.name');
    expect($firstPackageName)->toBeString();

    $package = Package::query()->where('is_active', true)->orderBy('sort_order')->first();
    expect($package)->not->toBeNull();
    $package->update(['name' => 'Mutated Package Name ' . uniqid('', true)]);

    $this->getJson('/api/v1/app/auth/registration')
        ->assertStatus(200)
        ->assertJsonPath('data.packages.0.name', $firstPackageName);
});

it('invalidates registration options cache when a package is updated via service', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);
    $this->seed(PackageCatalogSeeder::class);

    Cache::forget(CacheKeys::registrationOptions());

    $this->getJson('/api/v1/app/auth/registration')->assertStatus(200);
    expect(Cache::has(CacheKeys::registrationOptions()))->toBeTrue();

    $package = Package::query()->where('is_active', true)->first();
    expect($package)->not->toBeNull();

    $newName = 'Updated Via Service ' . uniqid('', true);
    $actor = $this->createUserWithRole('admin', 'pkg-cache-admin@example.com');
    app(PackageService::class)->updatePackage($package, ['name' => $newName], $actor);

    expect(Cache::has(CacheKeys::registrationOptions()))->toBeFalse();

    $this->getJson('/api/v1/app/auth/registration')
        ->assertStatus(200)
        ->assertJsonFragment(['name' => $newName]);
});
