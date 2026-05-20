<?php

declare(strict_types=1);
use App\Models\Package;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;

const AUTH_CANDIDATE_REGISTRATION_PW = 'Password@reg1';
beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);
});

it('returns selectable packages and surname options for registration', function () {
    $response = $this->getJson('/api/v1/auth/registration');
    $response
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'packages' => [['uuid', 'name', 'code', 'monthlyPrice', 'yearlyPrice', 'isDefaultRegistration']],
                'surnames' => [['id', 'name']],
            ],
        ]);

    $names = collect($response->json('data.surnames'))->pluck('name')->all();
    expect($names)->toContain('Chandrakar');
});

it('creates users with active subscriptions and package permissions from candidate registration', function () {
    $packageUuid = (string) Package::query()->where('code', 'PARICHAY_FREE')->value('uuid');
    expect($packageUuid)->not->toBeEmpty();

    $email = 'candidate-reg-' . uniqid('', true) . '@example.com';

    $response = $this->postJson('/api/v1/auth/register-candidate', [
        'first_name' => 'Test',
        'last_name' => 'Chandrakar',
        'email' => $email,
        'gender' => 'female',
        'date_of_birth' => '1995-06-15',
        'phone' => '9876543210',
        'password' => AUTH_CANDIDATE_REGISTRATION_PW,
        'password_confirmation' => AUTH_CANDIDATE_REGISTRATION_PW,
        'package_uuid' => $packageUuid,
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true)->assertJsonPath('data.token_type', 'Bearer');
    $response->assertJsonMissingPath('data.payment');

    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('candidate'))->toBeTrue();
    $candidateRoleId = (int) Role::query()->where('name', 'candidate')->where('guard_name', 'web')->value('id');
    expect((int) $user->role_id)->toBe($candidateRoleId);
    expect($user->first_name)->toBe('Test');
    expect($user->last_name)->toBe('Chandrakar');
    expect($user->getAllPermissions()->pluck('name')->contains('candidate.browse_profiles.limited'))->toBeTrue();

    $packageId = (int) Package::query()->where('code', 'PARICHAY_FREE')->value('id');
    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id,
        'package_id' => $packageId,
        'subscription_status' => 'active',
    ]);
});

it('returns validation errors for unknown package UUIDs during registration', function () {
    $response = $this->postJson('/api/v1/auth/register-candidate', [
        'first_name' => 'Test',
        'last_name' => 'Chandrakar',
        'email' => 'bad-pkg-' . uniqid('', true) . '@example.com',
        'gender' => 'male',
        'date_of_birth' => '1990-01-01',
        'phone' => '9876543211',
        'password' => AUTH_CANDIDATE_REGISTRATION_PW,
        'password_confirmation' => AUTH_CANDIDATE_REGISTRATION_PW,
        'package_uuid' => '00000000-0000-4000-8000-000000000001',
    ]);

    $response->assertStatus(422);
});

it('returns validation errors when surname is not in the community allow list', function () {
    $packageUuid = (string) Package::query()->where('code', 'PARICHAY_FREE')->value('uuid');

    $response = $this->postJson('/api/v1/auth/register-candidate', [
        'first_name' => 'Test',
        'last_name' => 'UnknownSurnameXYZ',
        'email' => 'bad-surname-' . uniqid('', true) . '@example.com',
        'gender' => 'male',
        'date_of_birth' => '1990-01-01',
        'phone' => '9876543212',
        'password' => AUTH_CANDIDATE_REGISTRATION_PW,
        'password_confirmation' => AUTH_CANDIDATE_REGISTRATION_PW,
        'package_uuid' => $packageUuid,
    ]);

    $response->assertStatus(422);
});

it('supports email-free registration when phone credentials are supplied and login still works', function () {
    $packageUuid = (string) Package::query()->where('code', 'PARICHAY_FREE')->value('uuid');
    $phone = '98765' . substr((string) time(), -5);

    $response = $this->postJson('/api/v1/auth/register-candidate', [
        'first_name' => 'No',
        'last_name' => 'Chandrakar',
        'gender' => 'female',
        'date_of_birth' => '1995-06-15',
        'phone' => $phone,
        'password' => AUTH_CANDIDATE_REGISTRATION_PW,
        'password_confirmation' => AUTH_CANDIDATE_REGISTRATION_PW,
        'package_uuid' => $packageUuid,
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $user = User::query()->where('phone', $phone)->first();
    expect($user)->not->toBeNull();
    expect($user->email)->toBeNull();

    $this->postJson('/api/v1/auth/login', [
        'username' => $phone,
        'password' => AUTH_CANDIDATE_REGISTRATION_PW,
    ])
        ->assertStatus(200)
        ->assertJsonPath('success', true);
});

it('returns validation errors when registering a duplicate email address', function () {
    $packageUuid = (string) Package::query()->where('code', 'PARICHAY_FREE')->value('uuid');
    $email = 'dup-' . uniqid('', true) . '@example.com';

    $this->postJson('/api/v1/auth/register-candidate', [
        'first_name' => 'A',
        'last_name' => 'Verma',
        'email' => $email,
        'gender' => 'male',
        'date_of_birth' => '1992-01-01',
        'phone' => '9876543213',
        'password' => AUTH_CANDIDATE_REGISTRATION_PW,
        'password_confirmation' => AUTH_CANDIDATE_REGISTRATION_PW,
        'package_uuid' => $packageUuid,
    ])->assertStatus(201);

    $this->postJson('/api/v1/auth/register-candidate', [
        'first_name' => 'B',
        'last_name' => 'Verma',
        'email' => $email,
        'gender' => 'female',
        'date_of_birth' => '1993-01-01',
        'phone' => '9876543214',
        'password' => AUTH_CANDIDATE_REGISTRATION_PW,
        'password_confirmation' => AUTH_CANDIDATE_REGISTRATION_PW,
        'package_uuid' => $packageUuid,
    ])->assertStatus(422);
});

it('omits inactive catalog packages from registration option responses', function () {
    $inactiveId = (int) Package::query()->where('code', 'RISHTA_PRO')->value('id');
    DB::table('packages')
        ->where('id', $inactiveId)
        ->update(['is_active' => false]);

    $uuids = collect($this->getJson('/api/v1/auth/registration')->json('data.packages'))
        ->pluck('uuid')
        ->all();

    $inactiveUuid = (string) Package::query()->where('id', $inactiveId)->value('uuid');
    expect($uuids)->not->toContain($inactiveUuid);
});
