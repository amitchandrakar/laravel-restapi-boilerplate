<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\PackagePermissionService;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CandidatePackagePermissionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_parichay_candidate_gets_limited_browse_permission_only(): void
    {
        [$candidate, $service] = $this->seedAndCreateCandidate('candidate-parichay@example.com');

        $this->upsertActiveSubscription($candidate->id, 'PARICHAY_FREE');
        $service->syncCandidatePermissions($candidate->refresh());

        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.browse_profiles.limited'));
        $this->assertFalse($candidate->fresh()->hasPermissionTo('candidate.browse_profiles.full'));
        $this->assertFalse($candidate->fresh()->hasPermissionTo('candidate.view_full_profile_details'));
    }

    public function test_talash_candidate_gets_expected_permission_set(): void
    {
        [$candidate, $service] = $this->seedAndCreateCandidate('candidate-talash@example.com');

        $this->upsertActiveSubscription($candidate->id, 'TALASH_BASIC');
        $service->syncCandidatePermissions($candidate->refresh());

        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.browse_profiles.full'));
        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.view_full_profile_details'));
        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.send_contact_requests'));
        $this->assertFalse($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'));
    }

    public function test_rishta_candidate_gets_full_permission_set(): void
    {
        [$candidate, $service] = $this->seedAndCreateCandidate('candidate-rishta@example.com');

        $this->upsertActiveSubscription($candidate->id, 'RISHTA_PRO');
        $service->syncCandidatePermissions($candidate->refresh());

        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.view_partner_preferences_details'));
        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'));
        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.view_kundali_matching_results'));
    }

    public function test_upgrade_and_downgrade_syncs_permissions_immediately_via_observer(): void
    {
        [$candidate] = $this->seedAndCreateCandidate('candidate-upgrade@example.com');

        $subscription = $this->createSubscription($candidate->id, 'PARICHAY_FREE');
        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.browse_profiles.limited'));
        $this->assertFalse($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'));

        $subscription->update(['package_id' => $this->packageIdByCode('RISHTA_PRO')]);
        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'));

        $subscription->update(['package_id' => $this->packageIdByCode('TALASH_BASIC')]);
        $this->assertFalse($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'));
        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.view_full_profile_details'));
    }

    public function test_inactive_subscription_removes_package_permissions(): void
    {
        [$candidate] = $this->seedAndCreateCandidate('candidate-inactive@example.com');

        $subscription = $this->createSubscription($candidate->id, 'RISHTA_PRO');
        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'));

        $subscription->update(['subscription_status' => 'expired']);
        $this->assertFalse($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'));
        $this->assertFalse($candidate->fresh()->hasPermissionTo('candidate.browse_profiles.full'));
    }

    /**
     * @return array{0: User, 1: PackagePermissionService}
     */
    private function seedAndCreateCandidate(string $email): array
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        /** @var User $candidate */
        $candidate = User::query()->create([
            'first_name' => 'Candidate',
            'last_name' => 'Test',
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
        ]);
        $candidate->assignRole('candidate');

        /** @var PackagePermissionService $service */
        $service = app(PackagePermissionService::class);

        return [$candidate, $service];
    }

    private function upsertActiveSubscription(int $userId, string $packageCode): void
    {
        $now = now();
        DB::table('subscriptions')->updateOrInsert(
            ['user_id' => $userId, 'package_id' => $this->packageIdByCode($packageCode)],
            [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'subscription_status' => 'active',
                'started_at' => $now,
                'ends_at' => $now->copy()->addYear(),
                'auto_renew' => false,
                'renewal_source' => 'manual',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    private function createSubscription(int $userId, string $packageCode): Subscription
    {
        /** @var Subscription $subscription */
        $subscription = Subscription::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $userId,
            'package_id' => $this->packageIdByCode($packageCode),
            'subscription_status' => 'active',
            'started_at' => now(),
            'ends_at' => now()->copy()->addYear(),
            'auto_renew' => false,
            'renewal_source' => 'manual',
        ]);

        return $subscription;
    }

    private function packageIdByCode(string $code): int
    {
        return (int) DB::table('packages')->where('code', $code)->value('id');
    }
}
