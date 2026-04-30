<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPaymentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_view_payments(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-payment-list@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-payment-list@example.com');
        $package = $this->createPackage('PAYMENT_LIST_PLAN');
        $payment = $this->createPayment($candidate, $package);

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
    }

    public function test_admin_can_update_payment_and_track_history(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-payment-update@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-payment-update@example.com');
        $package = $this->createPackage('PAYMENT_UPDATE_PLAN');
        $subscription = $this->createSubscription($candidate, $package);
        $payment = $this->createPayment($candidate, $package, $subscription);

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
    }

    public function test_admin_can_delete_payment(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-payment-delete@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-payment-delete@example.com');
        $package = $this->createPackage('PAYMENT_DELETE_PLAN');
        $payment = $this->createPayment($candidate, $package);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/payments/' . $payment->uuid)
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_non_existing_payment_returns_not_found_on_delete(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-payment-missing-delete@example.com');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/payments/' . (string) Str::uuid())
            ->assertStatus(404);
    }

    public function test_candidate_cannot_access_admin_payment_crud_endpoints(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'candidate-payment-forbidden-crud@example.com');
        $package = $this->createPackage('PAYMENT_FORBIDDEN_PLAN');
        $payment = $this->createPayment($candidate, $package);

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
    }

    public function test_payment_validation_applies_business_rules(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-payment-rules@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-payment-rules@example.com');
        $package = $this->createPackage('PAYMENT_RULES_PLAN');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/payments', [
                'user_id' => $candidate->id,
                'package_id' => $package->id,
                'amount' => -1,
                'currency' => 'INRR',
                'payment_status' => 'unknown',
            ])
            ->assertStatus(422);
    }

    private function createUserWithRole(string $role, string $email): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function createPackage(string $code): Package
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

    private function createSubscription(User $candidate, Package $package): Subscription
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

    private function createPayment(User $candidate, Package $package, ?Subscription $subscription = null): Payment
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
}
