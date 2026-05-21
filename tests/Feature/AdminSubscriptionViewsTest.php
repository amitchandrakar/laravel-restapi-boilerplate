<?php

declare(strict_types=1);

use App\Models\Package;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

it('lists active subscriptions with candidate and package details', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-sub-active@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-sub-active@example.com');
    $package = subscriptionTestPackage('ACTIVE_PLAN');
    subscriptionTestInsert($candidate->id, $package->id, 'active', now()->addMonths(6));

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/subscriptions/active')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.candidate.uuid', $candidate->uuid)
        ->assertJsonPath('data.0.package.name', 'ACTIVE_PLAN Name')
        ->assertJsonPath('data.0.package.price', 1000);
});

it('lists subscriptions expiring within seven days', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-sub-expiring@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-sub-expiring@example.com');
    $package = subscriptionTestPackage('EXPIRING_PLAN');
    subscriptionTestInsert($candidate->id, $package->id, 'active', now()->addDays(3));

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/subscriptions/expiring-soon')
        ->assertStatus(200)
        ->assertJsonPath('data.0.candidate.fullName', trim($candidate->first_name . ' ' . $candidate->last_name));
});

it('lists expired subscriptions', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-sub-expired@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-sub-expired@example.com');
    $package = subscriptionTestPackage('EXPIRED_PLAN');
    subscriptionTestInsert($candidate->id, $package->id, 'expired', now()->subDays(2));

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/subscriptions/expired')
        ->assertStatus(200)
        ->assertJsonPath('data.0.subscriptionStatus', 'expired');
});

it('returns subscription history for a candidate uuid', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-sub-history@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-sub-history@example.com');
    $package = subscriptionTestPackage('HISTORY_PLAN');
    subscriptionTestInsert($candidate->id, $package->id, 'cancelled', now()->subYear());
    subscriptionTestInsert($candidate->id, $package->id, 'active', now()->addYear());

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/subscriptions/history/' . $candidate->uuid)
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(2);
});

it('returns 404 for subscription history when user is not a candidate', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-sub-not-candidate@example.com');
    $reviewer = $this->createUserWithRole('reviewer', 'reviewer-sub-history@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/subscriptions/history/' . $reviewer->uuid)
        ->assertStatus(404);
});

it('denies subscription views without permission', function (): void {
    $candidate = $this->createUserWithRole('candidate', 'candidate-no-sub-view@example.com');

    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/subscriptions/active')->assertStatus(403);
});

function subscriptionTestPackage(string $code): Package
{
    /** @var Package $package */
    $package = Package::query()->create([
        'name' => $code . ' Name',
        'code' => $code,
        'description' => 'Test',
        'duration_unit' => 'year',
        'monthly_price' => 100,
        'yearly_price' => 1000,
        'price' => 1000,
        'currency' => 'INR',
        'is_active' => true,
        'is_default_registration' => false,
        'is_popular' => false,
        'sort_order' => 1,
    ]);

    return $package;
}

function subscriptionTestInsert(int $userId, int $packageId, string $status, $endsAt): void
{
    DB::table('subscriptions')->insert([
        'uuid' => (string) Str::uuid(),
        'user_id' => $userId,
        'package_id' => $packageId,
        'subscription_status' => $status,
        'started_at' => now()->subMonth(),
        'ends_at' => $endsAt,
        'auto_renew' => false,
        'renewal_source' => 'manual',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
