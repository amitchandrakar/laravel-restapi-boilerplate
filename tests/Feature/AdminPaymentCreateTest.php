<?php

declare(strict_types=1);
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Str;

it('allows admins with permission to record a payment and sync the subscription', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-payment-create@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-payment-create@example.com');
    $package = paymentCreateMakePackage();
    $subscription = paymentCreateMakeSubscription($candidate, $package);

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/payments', [
        'user_id' => $candidate->id,
        'subscription_id' => $subscription->id,
        'package_id' => $package->id,
        'gateway_name' => 'razorpay',
        'gateway_order_id' => 'order_123',
        'gateway_payment_id' => 'pay_123',
        'amount' => 4999,
        'currency' => 'inr',
        'payment_status' => 'success',
        'payment_method' => 'upi',
        'paid_at' => now()->toISOString(),
        'raw_response_json' => ['status' => 'captured'],
    ]);

    $response
        ->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.userId', $candidate->id)
        ->assertJsonPath('data.subscriptionId', $subscription->id)
        ->assertJsonPath('data.packageId', $package->id)
        ->assertJsonPath('data.currency', 'INR')
        ->assertJsonPath('data.paymentStatus', 'success');

    $paymentId = (int) $response->json('data.id');

    $this->assertDatabaseHas('payments', [
        'id' => $paymentId,
        'subscription_id' => $subscription->id,
        'payment_status' => 'success',
    ]);
    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'last_payment_id' => $paymentId,
        'subscription_status' => 'active',
    ]);
    $this->assertDatabaseHas('user_payment_history', [
        'payment_id' => $paymentId,
        'history_type' => 'confirmed',
    ]);
});

it('returns forbidden when a candidate posts to admin payments without permission', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-payment-forbidden@example.com');
    $package = paymentCreateMakePackage();

    $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/admin/payments', [
            'user_id' => $candidate->id,
            'package_id' => $package->id,
            'amount' => 999,
            'payment_status' => 'pending',
        ])
        ->assertStatus(403);
});
function paymentCreateMakePackage(): Package
{
    /** @var Package $package */
    $package = Package::query()->create([
        'name' => 'Payment Plan',
        'code' => 'PAYMENT_PLAN',
        'duration_unit' => 'year',
        'monthly_price' => 500,
        'yearly_price' => 5000,
        'price' => 5000,
        'currency' => 'INR',
        'is_active' => true,
    ]);

    return $package;
}
function paymentCreateMakeSubscription(User $candidate, Package $package): Subscription
{
    /** @var Subscription $subscription */
    $subscription = Subscription::query()->create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $candidate->id,
        'package_id' => $package->id,
        'subscription_status' => 'pending',
        'started_at' => now(),
        'ends_at' => now()->addYear(),
        'auto_renew' => false,
        'renewal_source' => 'manual',
    ]);

    return $subscription;
}
