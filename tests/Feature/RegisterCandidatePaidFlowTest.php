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

class RegisterCandidatePaidFlowTest extends TestCase
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

    public function test_paid_package_registration_returns_payment_block_and_pending_subscription(): void
    {
        $this->mock(RazorpayClient::class, function ($mock): void {
            $mock->shouldReceive('createOrder')->once()->andReturn([
                'id' => 'order_reg_test_1',
                'amount' => 36500,
                'currency' => 'INR',
            ]);
        });

        $packageUuid = (string) Package::query()->where('code', 'TALASH_BASIC')->value('uuid');
        $email = 'paid-reg-' . uniqid('', true) . '@example.com';

        $response = $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'Paid',
            'last_name' => 'Chandrakar',
            'email' => $email,
            'gender' => 'female',
            'date_of_birth' => '1995-06-15',
            'phone' => '9876543299',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => $packageUuid,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment.orderId', 'order_reg_test_1')
            ->assertJsonPath('data.payment.amount', 36500)
            ->assertJsonPath('data.payment.checkoutOptions.method.upi', true)
            ->assertJsonPath('data.payment.checkoutOptions.method.card', false);

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $packageId = (int) Package::query()->where('code', 'TALASH_BASIC')->value('id');
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package_id' => $packageId,
            'subscription_status' => 'pending',
        ]);

        $this->assertFalse($user->fresh()->getAllPermissions()->pluck('name')->contains('candidate.browse_profiles.full'));

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'package_id' => $packageId,
            'gateway_name' => 'razorpay',
            'gateway_order_id' => 'order_reg_test_1',
            'payment_status' => 'pending',
        ]);
    }
}
