<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\User;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

const REGISTER_PAID_FLOW_PW = 'Password@reg1';

beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);
});

it('requires payment for paid catalog packages without a coupon', function () {
    $packageUuid = (string) Package::query()->where('code', 'TALASH_BASIC')->value('uuid');
    $email = 'paid-reg-' . uniqid('', true) . '@example.com';

    $this->postJson('/api/v1/app/auth/register-candidate', [
        'first_name' => 'Paid',
        'last_name' => 'Chandrakar',
        'email' => $email,
        'gender' => 'female',
        'date_of_birth' => '1995-06-15',
        'phone' => '9876543299',
        'password' => REGISTER_PAID_FLOW_PW,
        'password_confirmation' => REGISTER_PAID_FLOW_PW,
        'package_uuid' => $packageUuid,
    ])->assertStatus(422);

    expect(User::query()->where('email', $email)->exists())->toBeFalse();
});

it('activates free catalog packages without payment', function () {
    $packageUuid = (string) Package::query()->where('code', 'PARICHAY_FREE')->value('uuid');
    $email = 'free-reg-' . uniqid('', true) . '@example.com';

    $this->postJson('/api/v1/app/auth/register-candidate', [
        'first_name' => 'Free',
        'last_name' => 'Chandrakar',
        'email' => $email,
        'gender' => 'female',
        'date_of_birth' => '1995-06-15',
        'phone' => '9876543297',
        'password' => REGISTER_PAID_FLOW_PW,
        'password_confirmation' => REGISTER_PAID_FLOW_PW,
        'package_uuid' => $packageUuid,
    ])
        ->assertStatus(201)
        ->assertJsonPath('success', true);

    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();
    $packageId = (int) Package::query()->where('code', 'PARICHAY_FREE')->value('id');

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id,
        'package_id' => $packageId,
        'subscription_status' => 'active',
    ]);

    $endsAt = Carbon::parse(
        (string) DB::table('subscriptions')
            ->where('user_id', $user->id)
            ->where('package_id', $packageId)
            ->value('ends_at')
    );

    expect($endsAt->isFuture())->toBeTrue();
    expect(
        $user->fresh()->getAllPermissions()->pluck('name')->contains('candidate.browse_profiles.limited')
    )->toBeTrue();
});
