<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Services\Payment\RazorpayClient;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationPaymentConfirmTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Password@reg1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);
        $this->seed(DemoMasterDataSeeder::class);
    }

    public function test_confirm_activates_subscription_and_grants_permissions(): void
    {
        $this->mock(RazorpayClient::class, function ($mock): void {
            $mock
                ->shouldReceive('createOrder')
                ->once()
                ->andReturn([
                    'id' => 'order_confirm_test_1',
                    'amount' => 36500,
                    'currency' => 'INR',
                ]);
            $mock->shouldReceive('verifyCheckoutSignature')->once()->andReturn(true);
        });

        $packageUuid = (string) Package::query()->where('code', 'TALASH_BASIC')->value('uuid');
        $email = 'confirm-reg-' . uniqid('', true) . '@example.com';

        $reg = $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'Confirm',
            'last_name' => 'Chandrakar',
            'email' => $email,
            'gender' => 'female',
            'date_of_birth' => '1995-06-15',
            'phone' => '9876543288',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => $packageUuid,
        ]);
        $reg->assertStatus(201);
        $token = (string) $reg->json('data.token');

        $this->postJson(
            '/api/v1/auth/payment/registration/confirm',
            [
                'razorpay_order_id' => 'order_confirm_test_1',
                'razorpay_payment_id' => 'pay_confirm_test_1',
                'razorpay_signature' => 'sig_test',
            ],
            ['Authorization' => 'Bearer ' . $token]
        )
            ->assertStatus(200)
            ->assertJsonPath('data.paymentStatus', 'success')
            ->assertJsonPath('data.subscription.status', 'active');

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertTrue(
            $user->fresh()->getAllPermissions()->pluck('name')->contains('candidate.browse_profiles.full')
        );

        $this->assertSame(1, $user->notifications()->where('data->kind', 'payment_succeeded')->count());
    }

    public function test_confirm_rejects_invalid_signature(): void
    {
        $this->mock(RazorpayClient::class, function ($mock): void {
            $mock
                ->shouldReceive('createOrder')
                ->once()
                ->andReturn([
                    'id' => 'order_bad_sig_1',
                    'amount' => 36500,
                    'currency' => 'INR',
                ]);
            $mock->shouldReceive('verifyCheckoutSignature')->once()->andReturn(false);
        });

        $packageUuid = (string) Package::query()->where('code', 'TALASH_BASIC')->value('uuid');
        $email = 'bad-sig-' . uniqid('', true) . '@example.com';

        $reg = $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'Bad',
            'last_name' => 'Chandrakar',
            'email' => $email,
            'gender' => 'female',
            'date_of_birth' => '1995-06-15',
            'phone' => '9876543277',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => $packageUuid,
        ]);
        $token = (string) $reg->json('data.token');

        $this->postJson(
            '/api/v1/auth/payment/registration/confirm',
            [
                'razorpay_order_id' => 'order_bad_sig_1',
                'razorpay_payment_id' => 'pay_x',
                'razorpay_signature' => 'bad',
            ],
            ['Authorization' => 'Bearer ' . $token]
        )->assertStatus(422);
    }

    public function test_confirm_twice_returns_conflict(): void
    {
        $this->mock(RazorpayClient::class, function ($mock): void {
            $mock
                ->shouldReceive('createOrder')
                ->once()
                ->andReturn([
                    'id' => 'order_double_1',
                    'amount' => 36500,
                    'currency' => 'INR',
                ]);
            $mock->shouldReceive('verifyCheckoutSignature')->twice()->andReturn(true);
        });

        $packageUuid = (string) Package::query()->where('code', 'TALASH_BASIC')->value('uuid');
        $email = 'double-' . uniqid('', true) . '@example.com';

        $reg = $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'Dbl',
            'last_name' => 'Chandrakar',
            'email' => $email,
            'gender' => 'female',
            'date_of_birth' => '1995-06-15',
            'phone' => '9876543266',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => $packageUuid,
        ]);
        $token = (string) $reg->json('data.token');
        $headers = ['Authorization' => 'Bearer ' . $token];
        $body = [
            'razorpay_order_id' => 'order_double_1',
            'razorpay_payment_id' => 'pay_double_1',
            'razorpay_signature' => 'sig',
        ];

        $this->postJson('/api/v1/auth/payment/registration/confirm', $body, $headers)->assertStatus(200);
        $this->postJson('/api/v1/auth/payment/registration/confirm', $body, $headers)->assertStatus(409);
    }
}
