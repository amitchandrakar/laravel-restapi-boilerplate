<?php

declare(strict_types=1);
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('allows admins to list and inspect payments', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-payment-list@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-payment-list@example.com');
    $package = paymentCrudMakePackage('PAYMENT_LIST_PLAN');
    $payment = paymentCrudMakePayment($candidate, $package);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/payments?perPage=10')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.id', $payment->id);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/payments/' . $payment->uuid)
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $payment->id);
});

it('allows admins to patch payments while writing subscription history rows', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-payment-update@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-payment-update@example.com');
    $package = paymentCrudMakePackage('PAYMENT_UPDATE_PLAN');
    $subscription = paymentCrudMakeSubscription($candidate, $package);
    $payment = paymentCrudMakePayment($candidate, $package, $subscription);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/payments/' . $payment->uuid, [
            'payment_status' => 'success',
            'gateway_reference_id' => 'ref_update_1',
            'paid_at' => now()->toISOString(),
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.paymentStatus', 'success')
        ->assertJsonPath('data.gatewayReferenceId', 'ref_update_1');

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'last_payment_id' => $payment->id,
        'subscription_status' => 'active',
    ]);
    $this->assertDatabaseHas('user_payment_history', [
        'payment_id' => $payment->id,
        'history_type' => 'confirmed',
    ]);
});

it('allows admins to delete payments', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-payment-delete@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-payment-delete@example.com');
    $package = paymentCrudMakePackage('PAYMENT_DELETE_PLAN');
    $payment = paymentCrudMakePayment($candidate, $package);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/admin/payments/' . $payment->uuid)
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
});

it('returns not found when deleting an unknown payment UUID', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-payment-missing-delete@example.com');

    $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/admin/payments/' . (string) Str::uuid())
        ->assertStatus(404);
});

it('returns forbidden when a candidate hits admin payment CRUD endpoints', function (): void {
    $candidate = $this->createUserWithRole('candidate', 'candidate-payment-forbidden-crud@example.com');
    $package = paymentCrudMakePackage('PAYMENT_FORBIDDEN_PLAN');
    $payment = paymentCrudMakePayment($candidate, $package);

    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/payments')->assertStatus(403);
    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/admin/payments/' . $payment->uuid)
        ->assertStatus(403);
    $this->actingAs($candidate, 'sanctum')
        ->patchJson('/api/v1/admin/payments/' . $payment->uuid, ['payment_status' => 'failed'])
        ->assertStatus(403);
    $this->actingAs($candidate, 'sanctum')
        ->deleteJson('/api/v1/admin/payments/' . $payment->uuid)
        ->assertStatus(403);
});

it('runs server-side validation rules on payment mutations', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-payment-rules@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-payment-rules@example.com');
    $package = paymentCrudMakePackage('PAYMENT_RULES_PLAN');

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/payments', [
            'user_id' => $candidate->id,
            'package_id' => $package->id,
            'amount' => -1,
            'currency' => 'INRR',
            'payment_status' => 'unknown',
        ])
        ->assertStatus(422);
});
function paymentCrudMakePackage(string $code): Package
{
    /** @var Package $package */
    $package = Package::query()->create([
        'name' => $code . ' Name',
        'code' => $code,
        'duration_unit' => 'year',
        'monthly_price' => 200,
        'yearly_price' => 2000,
        'price' => 2000,
        'currency' => 'INR',
        'is_active' => true,
    ]);

    return $package;
}
function paymentCrudMakeSubscription(User $candidate, Package $package): Subscription
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
function paymentCrudMakePayment(User $candidate, Package $package, ?Subscription $subscription = null): Payment
{
    /** @var Payment $payment */
    $payment = Payment::query()->create([
        'user_id' => $candidate->id,
        'subscription_id' => $subscription?->id,
        'package_id' => $package->id,
        'amount' => 2000,
        'currency' => 'INR',
        'payment_status' => 'pending',
        'payment_method' => 'manual',
    ]);

    return $payment;
}
