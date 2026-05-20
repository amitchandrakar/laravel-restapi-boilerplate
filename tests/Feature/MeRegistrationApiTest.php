<?php

declare(strict_types=1);
use App\Models\Package;
use App\Models\User;
use App\Services\Payment\RazorpayClient;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

it('skips payment checkout for free packages during `/me` registration flows', function () {
    $email = 'me-checkout-' . uniqid('', true) . '@example.com';
    $register = $this->postJson('/api/v1/auth/register', [
        'name' => 'Me Checkout User',
        'email' => $email,
        'password' => 'secret',
    ]);
    $register->assertStatus(201);
    $token = (string) $register->json('data.token');

    $freeUuid = (string) Package::query()->where('code', 'PARICHAY_FREE')->value('uuid');

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/me/registration/checkout', ['package_uuid' => $freeUuid])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.skip_checkout', true);
});

it('creates Razorpay orders for paid `/me` checkout selections', function () {
    $this->mock(RazorpayClient::class, function ($mock): void {
        $mock
            ->shouldReceive('createOrder')
            ->once()
            ->andReturn([
                'id' => 'order_me_checkout_1',
                'amount' => 36500,
                'currency' => 'INR',
            ]);
    });

    $email = 'me-paid-' . uniqid('', true) . '@example.com';
    $register = $this->postJson('/api/v1/auth/register', [
        'name' => 'Me Paid User',
        'email' => $email,
        'password' => 'secret',
    ]);
    $register->assertStatus(201);
    $token = (string) $register->json('data.token');

    $paidUuid = (string) Package::query()->where('code', 'TALASH_BASIC')->value('uuid');

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/me/registration/checkout', ['package_uuid' => $paidUuid])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.skip_checkout', false)
        ->assertJsonPath('data.order_id', 'order_me_checkout_1');
});

it('reports structured onboarding payloads from `/me/registration/status`', function () {
    $email = 'me-status-' . uniqid('', true) . '@example.com';
    $register = $this->postJson('/api/v1/auth/register', [
        'name' => 'Me Status User',
        'email' => $email,
        'password' => 'secret',
    ]);
    $register->assertStatus(201);
    $token = (string) $register->json('data.token');
    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/v1/me/registration/status')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user_uuid', $user->uuid)
        ->assertJsonStructure(['data' => ['next_step', 'payment', 'kyc']]);
});

it('responds with 403 when profile UUID headers do not match the token subject', function () {
    $email = 'me-header-' . uniqid('', true) . '@example.com';
    $register = $this->postJson('/api/v1/auth/register', [
        'name' => 'Me Header User',
        'email' => $email,
        'password' => 'secret',
    ]);
    $token = (string) $register->json('data.token');

    $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'X-User-Profile-Uuid' => '00000000-0000-4000-8000-000000000099',
    ])
        ->getJson('/api/v1/me/registration/status')
        ->assertStatus(403);
});

it('captures multipart KYC uploads plus submission through `/me/kyc/*` endpoints', function () {
    Storage::fake('public');

    $email = 'me-kyc-' . uniqid('', true) . '@example.com';
    $register = $this->postJson('/api/v1/auth/register', [
        'name' => 'Me Kyc User',
        'email' => $email,
        'password' => 'secret',
    ]);
    $token = (string) $register->json('data.token');

    $session = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/me/kyc/upload-sessions')
        ->assertStatus(200)
        ->json('data.session_id');
    expect($session)->toBeString();

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->post('/api/v1/me/kyc/upload', [
            'session_id' => $session,
            'aadhaar_front' => UploadedFile::fake()->image('front.jpg', 80, 80),
            'aadhaar_back' => UploadedFile::fake()->image('back.jpg', 80, 80),
            'selfie' => UploadedFile::fake()->image('selfie.jpg', 80, 80),
        ])
        ->assertStatus(200);

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/me/kyc/submit', [
            'session_id' => $session,
            'document_number_masked' => 'XXXXXXXX9012',
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.verificationStatus', 'pending');
});

it('routes Razorpay signature validation through the same gateway as canonical webhooks', function () {
    $this->postJson('/api/v1/webhooks/razorpay', [], ['X-Razorpay-Signature' => 'bad'])->assertStatus(401);
});
