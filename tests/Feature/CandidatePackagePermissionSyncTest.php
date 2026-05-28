<?php

declare(strict_types=1);
use App\Models\Subscription;
use App\Models\User;
use App\Services\PackagePermissionService;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

it('scopes Parichay subscribers to the limited browse capability set', function (): void {
    [$candidate, $service] = seedAndCreateCandidate('candidate-parichay@example.com');

    upsertActiveSubscription($candidate->id, 'PARICHAY_FREE');
    $service->syncCandidatePermissions($candidate->refresh());

    expect($candidate->fresh()->hasPermissionTo('candidate.browse_profiles.limited'))->toBeTrue();
    expect($candidate->fresh()->hasPermissionTo('candidate.browse_profiles.full'))->toBeFalse();
    expect($candidate->fresh()->hasPermissionTo('candidate.view_full_profile_details'))->toBeFalse();
});

it('grants Talash subscribers the expected marketplace permissions', function () {
    [$candidate, $service] = seedAndCreateCandidate('candidate-talash@example.com');

    upsertActiveSubscription($candidate->id, 'TALASH_BASIC');
    $service->syncCandidatePermissions($candidate->refresh());

    $fresh = $candidate->fresh();

    expect($fresh->hasPermissionTo('candidate.browse_profiles.full'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.view_full_profile_details'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.send_contact_requests'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.mark_profiles_favorite'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.view_partner_preferences_details'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.view_lifestyle_details'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.view_profile_highlighting'))->toBeFalse();
    expect($fresh->hasPermissionTo('candidate.view_instant_contact_access'))->toBeFalse();
    expect($fresh->hasPermissionTo('candidate.view_my_matches'))->toBeFalse();
    expect($fresh->hasPermissionTo('candidate.generate_kundali'))->toBeFalse();
});

it('grants Rishta subscribers the full discovery and contact permissions', function () {
    [$candidate, $service] = seedAndCreateCandidate('candidate-rishta@example.com');

    upsertActiveSubscription($candidate->id, 'RISHTA_PRO');
    $service->syncCandidatePermissions($candidate->refresh());

    $fresh = $candidate->fresh();

    expect($fresh->hasPermissionTo('candidate.view_partner_preferences_details'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.view_my_matches'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.view_profile_highlighting'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.view_instant_contact_access'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.mark_profiles_favorite'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.generate_kundali'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.view_kundali_matching_results'))->toBeTrue();
});

it('recomputes permissions instantly when subscriptions upgrade or downgrade tiers', function () {
    [$candidate] = seedAndCreateCandidate('candidate-upgrade@example.com');

    $subscription = packageSyncMakeSubscription($candidate->id, 'PARICHAY_FREE');
    expect($candidate->fresh()->hasPermissionTo('candidate.browse_profiles.limited'))->toBeTrue();
    expect($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'))->toBeFalse();

    $subscription->update(['package_id' => packageIdByCode('RISHTA_PRO')]);
    expect($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'))->toBeTrue();

    $subscription->update(['package_id' => packageIdByCode('TALASH_BASIC')]);
    expect($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'))->toBeFalse();
    expect($candidate->fresh()->hasPermissionTo('candidate.view_full_profile_details'))->toBeTrue();
});

it('removes package-granted permissions whenever a subscription lapses', function () {
    [$candidate] = seedAndCreateCandidate('candidate-inactive@example.com');

    $subscription = packageSyncMakeSubscription($candidate->id, 'RISHTA_PRO');
    expect($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'))->toBeTrue();

    $subscription->update(['subscription_status' => 'expired']);
    expect($candidate->fresh()->hasPermissionTo('candidate.generate_kundali'))->toBeFalse();
    expect($candidate->fresh()->hasPermissionTo('candidate.browse_profiles.full'))->toBeFalse();
});
/**
 * @return array{0: User, 1: PackagePermissionService}
 */
function seedAndCreateCandidate(string $email): array
{
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
function upsertActiveSubscription(int $userId, string $packageCode): void
{
    $now = now();
    DB::table('subscriptions')->updateOrInsert(
        ['user_id' => $userId, 'package_id' => packageIdByCode($packageCode)],
        [
            'uuid' => (string) Str::uuid(),
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
function packageSyncMakeSubscription(int $userId, string $packageCode): Subscription
{
    /** @var Subscription $subscription */
    $subscription = Subscription::query()->create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $userId,
        'package_id' => packageIdByCode($packageCode),
        'subscription_status' => 'active',
        'started_at' => now(),
        'ends_at' => now()->copy()->addYear(),
        'auto_renew' => false,
        'renewal_source' => 'manual',
    ]);

    return $subscription;
}
function packageIdByCode(string $code): int
{
    return (int) DB::table('packages')->where('code', $code)->value('id');
}
