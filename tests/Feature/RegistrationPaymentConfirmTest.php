<?php

declare(strict_types=1);
use App\Models\Package;
use App\Models\User;
use App\Services\Payment\RazorpayClient;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;

const REGISTRATION_PAYMENT_CONFIRM_PW = 'Password@reg1';

beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);
});

it('activates subscriptions and permissions after a successful payment confirmation', function () {
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
        'password' => REGISTRATION_PAYMENT_CONFIRM_PW,
        'password_confirmation' => REGISTRATION_PAYMENT_CONFIRM_PW,
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
    expect($user)->not->toBeNull();
    expect($user->fresh()->getAllPermissions()->pluck('name')->contains('candidate.browse_profiles.full'))->toBeTrue();

    expect($user->notifications()->where('data->kind', 'payment_succeeded')->count())->toBe(1);
});

it('rejects payment confirmations that fail signature validation', function () {
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
        'password' => REGISTRATION_PAYMENT_CONFIRM_PW,
        'password_confirmation' => REGISTRATION_PAYMENT_CONFIRM_PW,
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
});

it('returns HTTP 409 when clients attempt duplicate payment confirmations', function () {
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
        'password' => REGISTRATION_PAYMENT_CONFIRM_PW,
        'password_confirmation' => REGISTRATION_PAYMENT_CONFIRM_PW,
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
});