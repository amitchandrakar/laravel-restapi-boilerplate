<?php

declare(strict_types=1);
use App\Models\Coupon;
use App\Models\Package;
use App\Models\User;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Clear actingAs / guard user left by a previous test in this process.
    $this->app['auth']->forgetGuards();
    config(['auth.defaults.guard' => 'web']);

    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

it('skips payment checkout for free packages during `/me` registration flows', function () {
    $email = 'me-checkout-' . uniqid('', true) . '@example.com';
    $register = $this->postJson('/api/v1/app/auth/register', [
        'name' => 'Me Checkout User',
        'email' => $email,
        'password' => 'secret',
    ]);
    $register->assertStatus(201);
    $token = (string) $register->json('data.token');

    $freeUuid = (string) Package::query()->where('code', 'PARICHAY_FREE')->value('uuid');

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/app/me/registration/checkout', ['package_uuid' => $freeUuid])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.skip_checkout', true);
});

it('activates paid packages with a full discount coupon on checkout', function () {
    $paidPackage = Package::query()->where('code', 'TALASH_BASIC')->first();
    expect($paidPackage)->not->toBeNull();

    Coupon::query()->create([
        'code' => 'MEFREE',
        'name' => 'Checkout free',
        'discount_type' => 'percent',
        'discount_value' => 100,
        'package_id' => $paidPackage->id,
        'is_active' => true,
    ]);

    $email = 'me-paid-' . uniqid('', true) . '@example.com';
    $register = $this->postJson('/api/v1/app/auth/register', [
        'name' => 'Me Paid User',
        'email' => $email,
        'password' => 'secret',
    ]);
    $register->assertStatus(201);
    $token = (string) $register->json('data.token');

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/app/me/registration/checkout', [
            'package_uuid' => $paidPackage->uuid,
            'coupon_code' => 'MEFREE',
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.skip_checkout', true)
        ->assertJsonPath('data.reason', 'coupon_applied');

    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id,
        'package_id' => $paidPackage->id,
        'subscription_status' => 'active',
    ]);

    $endsAt = Carbon::parse(
        (string) DB::table('subscriptions')
            ->where('user_id', $user->id)
            ->where('package_id', $paidPackage->id)
            ->value('ends_at')
    );
    expect($endsAt->isFuture())->toBeTrue();
});

it('exposes catalog prices on registration options', function () {
    $response = $this->getJson('/api/v1/app/auth/registration')->assertStatus(200)->assertJsonPath('success', true);

    $packages = $response->json('data.packages');
    expect($packages)->toBeArray()->not->toBeEmpty();

    $talash = collect($packages)->firstWhere('code', 'TALASH_BASIC');
    expect($talash)->not->toBeNull();
    expect((float) $talash['registrationPayableRupees'])->toBe(365.0);
    expect($talash['availableCoupons'])->toBeArray();
});

it('reports structured onboarding payloads from `/me/registration/status`', function () {
    $email = 'me-status-' . uniqid('', true) . '@example.com';
    $register = $this->postJson('/api/v1/app/auth/register', [
        'name' => 'Me Status User',
        'email' => $email,
        'password' => 'secret',
    ]);
    $register->assertStatus(201);
    $token = (string) $register->json('data.token');
    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/v1/app/me/registration/status')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user_uuid', $user->uuid)
        ->assertJsonStructure(['data' => ['next_step', 'payment', 'kyc']]);
});

it('responds with 403 when profile UUID headers do not match the token subject', function () {
    $email = 'me-header-' . uniqid('', true) . '@example.com';
    $register = $this->postJson('/api/v1/app/auth/register', [
        'name' => 'Me Header User',
        'email' => $email,
        'password' => 'secret',
    ]);
    $token = (string) $register->json('data.token');

    $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'X-User-Profile-Uuid' => '00000000-0000-4000-8000-000000000099',
    ])
        ->getJson('/api/v1/app/me/registration/status')
        ->assertStatus(403);
});

it('exposes rejection_reason on registration status after KYC reject', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'me-kyc-reject-status@example.com');
    $reviewer = $this->createUserWithRole('reviewer', 'me-kyc-reject-reviewer@example.com');

    $uuid = (string) $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/app/auth/candidate/kyc/documents', [
            'document_type' => 'aadhaar',
            'document_front_url' => 'https://example.com/kyc/front-status.jpg',
            'document_back_url' => 'https://example.com/kyc/back-status.jpg',
        ])
        ->json('data.uuid');

    $this->actingAs($reviewer, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/kyc/documents/' . $uuid, [
            'verification_status' => 'rejected',
            'rejection_reason' => 'Blurry document photo',
        ])
        ->assertStatus(200);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/app/me/registration/status')
        ->assertStatus(200)
        ->assertJsonPath('data.kyc.status', 'rejected')
        ->assertJsonPath('data.kyc.rejection_reason', 'Blurry document photo');
});

it('captures multipart KYC uploads plus submission through `/me/kyc/*` endpoints', function () {
    Storage::fake('public');

    $email = 'me-kyc-' . uniqid('', true) . '@example.com';
    $register = $this->postJson('/api/v1/app/auth/register', [
        'name' => 'Me Kyc User',
        'email' => $email,
        'password' => 'secret',
    ])->assertCreated();
    $token = (string) $register->json('data.token');
    expect($token)->not->toBeEmpty();

    $session = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/app/me/kyc/upload-sessions')
        ->assertStatus(200)
        ->json('data.session_id');
    expect($session)->toBeString();

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->post('/api/v1/app/me/kyc/upload', [
            'session_id' => $session,
            'aadhaar_front' => UploadedFile::fake()->image('front.jpg', 80, 80),
            'aadhaar_back' => UploadedFile::fake()->image('back.jpg', 80, 80),
            'selfie' => UploadedFile::fake()->image('selfie.jpg', 80, 80),
        ])
        ->assertStatus(200);

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/app/me/kyc/submit', [
            'session_id' => $session,
            'document_number_masked' => 'XXXXXXXX9012',
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.verificationStatus', 'pending');
});
