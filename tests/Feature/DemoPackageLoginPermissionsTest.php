<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\PackagePermissionService;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

it('returns Parichay package permissions on shared login', function (): void {
    $user = seedPackageDemoCandidate('parichay-login@example.com', 'PARICHAY_FREE');

    $login = loginAsCandidate($user->email);

    $permissions = $login->json('data.permissions');
    expect($permissions)->toContain('candidate.browse_profiles.limited');
    expect($permissions)->not->toContain('candidate.browse_profiles.full');
    expect($permissions)->not->toContain('candidate.mark_profiles_favorite');
});

it('returns Talash package permissions on shared login', function (): void {
    $user = seedPackageDemoCandidate('talash-login@example.com', 'TALASH_BASIC');

    $login = loginAsCandidate($user->email);

    $permissions = $login->json('data.permissions');
    expect($permissions)->toContain('candidate.browse_profiles.full');
    expect($permissions)->toContain('candidate.mark_profiles_favorite');
    expect($permissions)->not->toContain('candidate.view_my_matches');
});

it('returns Rishta package permissions on shared login', function (): void {
    $user = seedPackageDemoCandidate('rishta-login@example.com', 'RISHTA_PRO');

    $login = loginAsCandidate($user->email);

    $permissions = $login->json('data.permissions');
    expect($permissions)->toContain('candidate.view_my_matches');
    expect($permissions)->toContain('candidate.generate_kundali');
    expect($permissions)->toContain('candidate.view_profile_highlighting');
});

function seedPackageDemoCandidate(string $email, string $packageCode): User
{
    /** @var User $user */
    $user = User::query()->create([
        'first_name' => 'Demo',
        'last_name' => 'Package',
        'email' => $email,
        'password' => 'Password@123',
        'status' => 'active',
    ]);
    $user->assignRole('candidate');

    $now = now();
    $packageId = (int) DB::table('packages')->where('code', $packageCode)->value('id');

    DB::table('subscriptions')->insert([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'package_id' => $packageId,
        'subscription_status' => 'active',
        'started_at' => $now,
        'ends_at' => $now->copy()->addYear(),
        'auto_renew' => false,
        'renewal_source' => 'manual',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    app(PackagePermissionService::class)->syncCandidatePermissions($user->fresh());

    return $user->fresh();
}

function loginAsCandidate(string $email): TestResponse
{
    return test()->postJson('/api/v1/auth/login', [
        'username' => $email,
        'password' => 'Password@123',
    ]);
}
