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

class RazorpayWebhookTest extends TestCase
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

    public function test_payment_captured_webhook_marks_payment_success(): void
    {
        $this->mock(RazorpayClient::class, function ($mock): void {
            $mock
                ->shouldReceive('createOrder')
                ->once()
                ->andReturn([
                    'id' => 'order_wh_1',
                    'amount' => 36500,
                    'currency' => 'INR',
                ]);
            $mock->shouldReceive('verifyWebhookSignature')->twice()->andReturn(true);
        });

        $packageUuid = (string) Package::query()->where('code', 'TALASH_BASIC')->value('uuid');
        $email = 'wh-' . uniqid('', true) . '@example.com';

        $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'Wh',
            'last_name' => 'Chandrakar',
            'email' => $email,
            'gender' => 'female',
            'date_of_birth' => '1995-06-15',
            'phone' => '9876543255',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => $packageUuid,
        ])->assertStatus(201);

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);

        $payload = [
            'id' => 'evt_wh_1',
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_wh_1',
                        'order_id' => 'order_wh_1',
                        'status' => 'captured',
                    ],
                ],
            ],
        ];

        $this->postJson('/api/v1/payment/razorpay/webhook', $payload, [
            'X-Razorpay-Signature' => 'testsig',
        ])->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'gateway_order_id' => 'order_wh_1',
            'payment_status' => 'success',
            'webhook_event_id' => 'evt_wh_1',
        ]);

        $packageId = (int) Package::query()->where('code', 'TALASH_BASIC')->value('id');
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package_id' => $packageId,
            'subscription_status' => 'active',
        ]);

        // Idempotent replay
        $this->postJson('/api/v1/payment/razorpay/webhook', $payload, [
            'X-Razorpay-Signature' => 'testsig',
        ])->assertStatus(200);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->mock(RazorpayClient::class, function ($mock): void {
            $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(false);
        });

        $this->postJson(
            '/api/v1/payment/razorpay/webhook',
            ['event' => 'payment.captured'],
            ['X-Razorpay-Signature' => 'bad']
        )->assertStatus(401);
    }

    public function test_payment_failed_webhook_marks_payment_failed(): void
    {
        $this->mock(RazorpayClient::class, function ($mock): void {
            $mock
                ->shouldReceive('createOrder')
                ->once()
                ->andReturn([
                    'id' => 'order_fail_1',
                    'amount' => 36500,
                    'currency' => 'INR',
                ]);
            $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        });

        $packageUuid = (string) Package::query()->where('code', 'TALASH_BASIC')->value('uuid');
        $email = 'wh-fail-' . uniqid('', true) . '@example.com';

        $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'Fail',
            'last_name' => 'Chandrakar',
            'email' => $email,
            'gender' => 'female',
            'date_of_birth' => '1995-06-15',
            'phone' => '9876543244',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => $packageUuid,
        ])->assertStatus(201);

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);

        $payload = [
            'id' => 'evt_fail_1',
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_fail_1',
                        'order_id' => 'order_fail_1',
                        'status' => 'failed',
                        'error_description' => 'Bank declined',
                    ],
                ],
            ],
        ];

        $this->postJson('/api/v1/payment/razorpay/webhook', $payload, [
            'X-Razorpay-Signature' => 'testsig',
        ])->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'gateway_order_id' => 'order_fail_1',
            'payment_status' => 'failed',
        ]);

        $this->assertSame(1, $user->fresh()->notifications()->where('data->kind', 'payment_failed')->count());
    }
}
