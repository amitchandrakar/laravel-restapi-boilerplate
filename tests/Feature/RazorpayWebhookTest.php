<?php

declare(strict_types=1);
use App\Models\Package;
use App\Models\User;
use App\Services\Payment\RazorpayClient;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;

const RAZORPAY_WEBHOOK_TEST_PW = 'Password@reg1';
beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);
});

it('marks payments successful when capture webhooks arrive with valid payloads', function () {
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
        'password' => RAZORPAY_WEBHOOK_TEST_PW,
        'password_confirmation' => RAZORPAY_WEBHOOK_TEST_PW,
        'package_uuid' => $packageUuid,
    ])->assertStatus(201);

    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();

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
    ])->assertStatus(202);

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
    ])->assertStatus(202);
});

it('rejects Razorpay webhooks that fail HMAC verification', function () {
    $this->mock(RazorpayClient::class, function ($mock): void {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(false);
    });

    $this->postJson(
        '/api/v1/payment/razorpay/webhook',
        ['event' => 'payment.captured'],
        ['X-Razorpay-Signature' => 'bad']
    )->assertStatus(401);
});

it('stores failed-payment state when Razorpay reports a capture failure', function () {
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
        'password' => RAZORPAY_WEBHOOK_TEST_PW,
        'password_confirmation' => RAZORPAY_WEBHOOK_TEST_PW,
        'package_uuid' => $packageUuid,
    ])->assertStatus(201);

    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();

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
    ])->assertStatus(202);

    $this->assertDatabaseHas('payments', [
        'user_id' => $user->id,
        'gateway_order_id' => 'order_fail_1',
        'payment_status' => 'failed',
    ]);

    expect($user->fresh()->notifications()->where('data->kind', 'payment_failed')->count())->toBe(1);
});
