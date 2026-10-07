<?php

declare(strict_types=1);

use App\Models\Coupon;
use App\Models\Package;
use App\Models\User;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Carbon;

const COUPON_TEST_PW = 'Password@coupon1';

beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);
});

it('validates an active percent coupon for a package', function () {
    $package = Package::query()->where('code', 'TALASH_BASIC')->first();
    expect($package)->not->toBeNull();

    Coupon::query()->create([
        'code' => 'SAVE20',
        'name' => 'Twenty percent off',
        'discount_type' => 'percent',
        'discount_value' => 20,
        'package_id' => $package->id,
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/app/auth/registration/validate-coupon', [
        'packageUuid' => $package->uuid,
        'couponCode' => 'save20',
    ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.valid', true)
        ->assertJsonPath('data.originalPrice', 365)
        ->assertJsonPath('data.discountedPrice', 292)
        ->assertJsonPath('data.coupon.code', 'SAVE20');
});

it('rejects expired coupons', function () {
    $package = Package::query()->where('code', 'TALASH_BASIC')->first();
    expect($package)->not->toBeNull();

    Coupon::query()->create([
        'code' => 'OLDDEAL',
        'name' => 'Expired',
        'discount_type' => 'fixed',
        'discount_value' => 100,
        'package_id' => $package->id,
        'expires_at' => now()->subDay(),
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/app/auth/registration/validate-coupon', [
        'packageUuid' => $package->uuid,
        'couponCode' => 'OLDDEAL',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.valid', false);
});

it('rejects coupons that exceeded max uses', function () {
    $package = Package::query()->where('code', 'TALASH_BASIC')->first();
    expect($package)->not->toBeNull();

    $coupon = Coupon::query()->create([
        'code' => 'LIMIT1',
        'name' => 'One use',
        'discount_type' => 'percent',
        'discount_value' => 100,
        'package_id' => $package->id,
        'max_uses' => 1,
        'uses_count' => 1,
        'is_active' => true,
    ]);

    expect($coupon)->not->toBeNull();

    $this->postJson('/api/v1/app/auth/registration/validate-coupon', [
        'packageUuid' => $package->uuid,
        'couponCode' => 'LIMIT1',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.valid', false);
});

it('registers a candidate with a full discount coupon and records redemption', function () {
    $package = Package::query()->where('code', 'TALASH_BASIC')->first();
    expect($package)->not->toBeNull();

    Coupon::query()->create([
        'code' => 'FREE100',
        'name' => 'Full discount',
        'discount_type' => 'percent',
        'discount_value' => 100,
        'package_id' => $package->id,
        'is_active' => true,
    ]);

    $email = 'coupon-reg-' . uniqid('', true) . '@example.com';

    $this->postJson('/api/v1/app/auth/register-candidate', [
        'first_name' => 'Coupon',
        'last_name' => 'Chandrakar',
        'email' => $email,
        'gender' => 'female',
        'date_of_birth' => '1995-06-15',
        'phone' => '9876543298',
        'password' => COUPON_TEST_PW,
        'password_confirmation' => COUPON_TEST_PW,
        'package_uuid' => $package->uuid,
        'coupon_code' => 'FREE100',
    ])
        ->assertStatus(201)
        ->assertJsonPath('success', true);

    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();

    $coupon = Coupon::query()->where('code', 'FREE100')->first();
    expect($coupon)->not->toBeNull();
    expect((int) $coupon->uses_count)->toBe(1);

    $this->assertDatabaseHas('coupon_redemptions', [
        'coupon_id' => $coupon->id,
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id,
        'package_id' => $package->id,
        'subscription_status' => 'active',
    ]);
});

it('rejects coupons that have not started yet', function () {
    $package = Package::query()->where('code', 'TALASH_BASIC')->first();
    expect($package)->not->toBeNull();

    Coupon::query()->create([
        'code' => 'FUTURE',
        'name' => 'Future start',
        'discount_type' => 'percent',
        'discount_value' => 50,
        'package_id' => $package->id,
        'starts_at' => now()->addDay(),
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/app/auth/registration/validate-coupon', [
        'packageUuid' => $package->uuid,
        'couponCode' => 'FUTURE',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.valid', false);
});

it('excludes future-start coupons from discounted registration prices', function () {
    $package = Package::query()->where('code', 'TALASH_BASIC')->first();
    expect($package)->not->toBeNull();

    Coupon::query()->create([
        'code' => 'LATER',
        'name' => 'Not yet',
        'discount_type' => 'percent',
        'discount_value' => 100,
        'package_id' => $package->id,
        'starts_at' => now()->addWeek(),
        'is_active' => true,
    ]);

    Coupon::query()->create([
        'code' => 'NOWOK',
        'name' => 'Available now',
        'discount_type' => 'fixed',
        'discount_value' => 25,
        'package_id' => $package->id,
        'starts_at' => now()->subHour(),
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/app/auth/registration')->assertStatus(200);

    $packages = $response->json('data.packages');
    $talash = collect($packages)->firstWhere('code', 'TALASH_BASIC');
    expect($talash)->not->toBeNull();
    // Future 100% coupon ignored; active ₹25-off coupon applied → 365 - 25 = 340
    expect((float) $talash['registrationPayableRupees'])->toBe(340.0);
});

it('creates a coupon with startsAt for admin', function () {
    $admin = $this->createUserWithRole('admin', 'admin-coupon-start@example.com');
    $package = Package::query()->where('code', 'TALASH_BASIC')->first();
    expect($package)->not->toBeNull();

    $starts = Carbon::now()->addDays(3)->startOfDay()->toIso8601String();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/coupons', [
            'code' => 'STARTSOON',
            'name' => 'Starts soon',
            'discountType' => 'percent',
            'discountValue' => 15,
            'packageUuid' => $package->uuid,
            'startsAt' => $starts,
            'isActive' => true,
        ])
        ->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.code', 'STARTSOON');

    $coupon = Coupon::query()->where('code', 'STARTSOON')->first();
    expect($coupon)->not->toBeNull();
    expect($coupon->starts_at)->not->toBeNull();
});

it('exposes coupon-discounted registration prices on registration options', function () {
    $package = Package::query()->where('code', 'TALASH_BASIC')->first();
    expect($package)->not->toBeNull();

    Coupon::query()->create([
        'code' => 'OPTSALE',
        'name' => 'Options sale',
        'discount_type' => 'fixed',
        'discount_value' => 50,
        'package_id' => $package->id,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/app/auth/registration')->assertStatus(200)->assertJsonPath('success', true);

    $packages = $response->json('data.packages');
    $talash = collect($packages)->firstWhere('code', 'TALASH_BASIC');
    expect($talash)->not->toBeNull();
    expect($talash['availableCoupons'])->toBeArray()->toBeEmpty();
    expect((float) $talash['registrationPayableRupees'])->toBe(315.0);
});

it('registers with a package-assigned coupon without a client coupon code', function () {
    $package = Package::query()->where('code', 'TALASH_BASIC')->first();
    expect($package)->not->toBeNull();

    Coupon::query()->create([
        'code' => 'AUTOFREE',
        'name' => 'Auto free',
        'discount_type' => 'percent',
        'discount_value' => 100,
        'package_id' => $package->id,
        'is_active' => true,
    ]);

    $email = 'auto-coupon-' . uniqid('', true) . '@example.com';

    $this->postJson('/api/v1/app/auth/register-candidate', [
        'first_name' => 'Auto',
        'last_name' => 'Chandrakar',
        'email' => $email,
        'gender' => 'female',
        'date_of_birth' => '1995-06-15',
        'phone' => '9876543290',
        'password' => COUPON_TEST_PW,
        'password_confirmation' => COUPON_TEST_PW,
        'package_uuid' => $package->uuid,
    ])
        ->assertStatus(201)
        ->assertJsonPath('success', true);

    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();
    $coupon = Coupon::query()->where('code', 'AUTOFREE')->first();
    expect($coupon)->not->toBeNull();
    expect((int) $coupon->uses_count)->toBe(1);
    $this->assertDatabaseHas('coupon_redemptions', [
        'coupon_id' => $coupon->id,
        'user_id' => $user->id,
    ]);
});

it('lists coupons for admin and enforces permissions', function () {
    $admin = $this->createUserWithRole('admin', 'admin-coupons@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-coupons@example.com');

    Coupon::query()->create([
        'code' => 'ADMINVIEW',
        'name' => 'Admin list test',
        'discount_type' => 'fixed',
        'discount_value' => 50,
        'is_active' => true,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/coupons')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonFragment(['code' => 'ADMINVIEW']);

    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/coupons')->assertStatus(403);
});
