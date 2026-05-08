<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Services\Payment\RazorpayClient;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MeRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);
    }

    public function test_me_registration_checkout_skips_for_free_package(): void
    {
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
    }

    public function test_me_registration_checkout_returns_order_for_paid_package(): void
    {
        $this->mock(RazorpayClient::class, function ($mock): void {
            $mock->shouldReceive('createOrder')->once()->andReturn([
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
    }

    public function test_me_registration_status_returns_payload(): void
    {
        $email = 'me-status-' . uniqid('', true) . '@example.com';
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Me Status User',
            'email' => $email,
            'password' => 'secret',
        ]);
        $register->assertStatus(201);
        $token = (string) $register->json('data.token');
        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me/registration/status')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_uuid', $user->uuid)
            ->assertJsonStructure(['data' => ['next_step', 'payment', 'kyc']]);
    }

    public function test_profile_uuid_header_mismatch_returns_403(): void
    {
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
    }

    public function test_me_kyc_multipart_submit_flow(): void
    {
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
        $this->assertIsString($session);

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
    }

    public function test_webhooks_razorpay_alias_matches_payment_webhook(): void
    {
        $this->postJson('/api/v1/webhooks/razorpay', [], ['X-Razorpay-Signature' => 'bad'])
            ->assertStatus(401);
    }
}
