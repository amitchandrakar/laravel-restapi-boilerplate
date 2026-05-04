<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\PackagePermissionService;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CandidateDiscoveryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_browse_lists_published_candidates_and_favorite_flag(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $viewer = $this->makeCandidate('browse-viewer@example.com');
        $this->subscribe($viewer, 'TALASH_BASIC');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $other = $this->makePublishedCandidate('browse-other@example.com');

        $res = $this->withToken($this->tokenFor($viewer))->getJson('/api/v1/auth/candidate/search');
        $res->assertStatus(200)->assertJsonPath('success', true);
        $items = $res->json('data');
        $this->assertIsArray($items);
        $found = collect($items)->firstWhere('uuid', $other->uuid);
        $this->assertNotNull($found);
        $this->assertArrayHasKey('isFavorite', $found);
        $this->assertFalse($found['isFavorite']);
        $this->assertSame('Software', $found['occupation']);
        $this->assertSame('not_submitted', $found['profileVerificationStatus']);

        $this->withToken($this->tokenFor($viewer))
            ->patchJson('/api/v1/auth/candidate/favorites/' . $other->uuid, ['favorite' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.favorite', true);

        $res2 = $this->withToken($this->tokenFor($viewer->fresh()))->getJson('/api/v1/auth/candidate/search');
        $found2 = collect($res2->json('data'))->firstWhere('uuid', $other->uuid);
        $this->assertTrue($found2['isFavorite']);
    }

    public function test_candidate_without_package_subscription_can_browse_and_use_discovery(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $viewer = $this->makeCandidate('browse-no-package@example.com');
        $other = $this->makePublishedCandidate('browse-other-nopkg@example.com');

        $t = $this->tokenFor($viewer);
        $this->withToken($t)->getJson('/api/v1/auth/candidate/search')->assertStatus(200);
        $this->withToken($t)
            ->patchJson('/api/v1/auth/candidate/favorites/' . $other->uuid, ['favorite' => true])
            ->assertStatus(200);
        $this->withToken($t)->getJson('/api/v1/auth/candidate/favorites')->assertStatus(200);
        $this->withToken($t)->getJson('/api/v1/auth/candidate/matches')->assertStatus(200);
    }

    public function test_discovery_forbidden_for_non_candidate_role(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $adminRoleId = (int) Role::query()->where('name', 'admin')->where('guard_name', 'web')->value('id');
        /** @var User $admin */
        $admin = User::query()->create([
            'first_name' => 'Admin',
            'last_name' => 'Staff',
            'email' => 'discovery-admin@example.com',
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => $adminRoleId,
        ]);
        $admin->assignRole('admin');

        $t = $this->tokenFor($admin);
        $this->withToken($t)->getJson('/api/v1/auth/candidate/search')->assertStatus(403);
        $this->withToken($t)->getJson('/api/v1/auth/candidate/favorites')->assertStatus(403);
        $this->withToken($t)->getJson('/api/v1/auth/candidate/matches')->assertStatus(403);
    }

    public function test_talash_candidate_can_list_and_toggle_favorites(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $viewer = $this->makeCandidate('fav-talash@example.com');
        $this->subscribe($viewer, 'TALASH_BASIC');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $target = $this->makePublishedCandidate('fav-target@example.com');

        $t = $this->tokenFor($viewer->fresh());
        $this->withToken($t)->getJson('/api/v1/auth/candidate/favorites')->assertStatus(200);
        $this->withToken($t)
            ->patchJson('/api/v1/auth/candidate/favorites/' . $target->uuid, ['favorite' => true])
            ->assertStatus(200);
    }

    public function test_favorites_list_returns_saved_favorites(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $viewer = $this->makeCandidate('fav-rishta@example.com');
        $this->subscribe($viewer, 'RISHTA_PRO');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $a = $this->makePublishedCandidate('fav-a@example.com');
        $b = $this->makePublishedCandidate('fav-b@example.com');

        $t = $this->tokenFor($viewer->fresh());
        $this->withToken($t)
            ->patchJson('/api/v1/auth/candidate/favorites/' . $a->uuid, ['favorite' => true])
            ->assertStatus(200);
        $this->withToken($t)
            ->patchJson('/api/v1/auth/candidate/favorites/' . $b->uuid, ['favorite' => true])
            ->assertStatus(200);

        $res = $this->withToken($t)->getJson('/api/v1/auth/candidate/favorites');
        $res->assertStatus(200)->assertJsonPath('success', true);
        $uuids = collect($res->json('data'))->pluck('uuid')->all();
        $this->assertContains($a->uuid, $uuids);
        $this->assertContains($b->uuid, $uuids);
    }

    public function test_matches_endpoint_returns_rows_for_any_candidate(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $viewer = $this->makeCandidate('match-viewer@example.com');
        $this->subscribe($viewer, 'TALASH_BASIC');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $matched = $this->makePublishedCandidate('match-target@example.com');
        $this->subscribe($matched, 'RISHTA_PRO');

        DB::table('matches')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $viewer->id,
            'matched_user_id' => $matched->id,
            'match_score' => 88,
            'match_reason_json' => json_encode(['summary' => 'Test reason'], JSON_THROW_ON_ERROR),
            'match_status' => 'active',
            'generated_by' => 'system',
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->withToken($this->tokenFor($viewer->fresh()))->getJson('/api/v1/auth/candidate/matches');
        $res->assertStatus(200)->assertJsonPath('success', true);
        $row = $res->json('data.0');
        $this->assertSame(88, $row['matchPercentage']);
        $this->assertTrue($row['hasPremiumSubscription']);
        $this->assertSame($matched->uuid, $row['uuid']);
        $this->assertArrayHasKey('matchReason', $row);
        $this->assertSame('Test reason', $row['matchReason']['summary'] ?? null);
    }

    public function test_discovery_list_endpoints_accept_same_query_filters(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);
        $this->seed(DemoMasterDataSeeder::class);

        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->insertGetId([
            'country_id' => $countryId,
            'name' => 'Chhattisgarh',
            'code' => 'CG',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cityId = (int) DB::table('cities')->insertGetId([
            'state_id' => $stateId,
            'name' => 'Raipur',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $surnameChandrakarId = (int) DB::table('surnames')->where('name', 'Chandrakar')->value('id');
        $degreeMbaId = (int) DB::table('degrees')->where('name', 'MBA')->value('id');
        $occEngineerId = (int) DB::table('occupations')->where('name', 'Software Engineer')->value('id');
        $occTeacherId = (int) DB::table('occupations')->where('name', 'Teacher')->value('id');

        Carbon::setTestNow('2026-05-02 12:00:00');

        $viewer = $this->makeCandidate('filter-viewer@example.com');
        $roleId = (int) Role::query()->where('name', 'candidate')->where('guard_name', 'web')->value('id');

        /** @var User $male */
        $male = User::query()->create([
            'first_name' => 'Male',
            'last_name' => 'Chandrakar',
            'email' => 'filter-male@example.com',
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => $roleId,
            'gender' => 'male',
            'current_city' => 'Raipur',
            'current_state' => 'Chhattisgarh',
            'occupation' => 'Software Engineer',
            'date_of_birth' => '1990-06-15',
            'profile_status' => 'published',
            'published_at' => now(),
        ]);
        $male->assignRole('candidate');
        DB::table('user_education_details')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $male->id,
            'degree_id' => $degreeMbaId,
            'education_type' => 'post_graduation',
            'is_highest' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var User $female */
        $female = User::query()->create([
            'first_name' => 'Female',
            'last_name' => 'Verma',
            'email' => 'filter-female@example.com',
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => $roleId,
            'gender' => 'female',
            'current_city' => 'Pune',
            'current_state' => 'Maharashtra',
            'occupation' => 'Teacher',
            'date_of_birth' => '1995-01-01',
            'profile_status' => 'published',
            'published_at' => now(),
        ]);
        $female->assignRole('candidate');

        $t = $this->tokenFor($viewer);
        $q = http_build_query([
            'gender' => 'male',
            'min_age' => 30,
            'max_age' => 40,
            'community' => [$surnameChandrakarId],
            'city' => 'Raipur',
            'education' => [$degreeMbaId],
            'occupation' => [$occEngineerId, $occTeacherId],
        ]);

        $search = $this->withToken($t)->getJson('/api/v1/auth/candidate/search?' . $q);
        $search->assertStatus(200);
        $this->assertSame([$male->uuid], collect($search->json('data'))->pluck('uuid')->all());

        $this->withToken($t)
            ->patchJson('/api/v1/auth/candidate/favorites/' . $male->uuid, ['favorite' => true])
            ->assertStatus(200);
        $this->withToken($t)
            ->patchJson('/api/v1/auth/candidate/favorites/' . $female->uuid, ['favorite' => true])
            ->assertStatus(200);

        $fav = $this->withToken($t)->getJson('/api/v1/auth/candidate/favorites?' . $q);
        $fav->assertStatus(200);
        $this->assertSame([$male->uuid], collect($fav->json('data'))->pluck('uuid')->all());

        DB::table('matches')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $viewer->id,
            'matched_user_id' => $male->id,
            'match_score' => 80,
            'match_reason_json' => null,
            'match_status' => 'active',
            'generated_by' => 'system',
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('matches')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $viewer->id,
            'matched_user_id' => $female->id,
            'match_score' => 70,
            'match_reason_json' => null,
            'match_status' => 'active',
            'generated_by' => 'system',
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matches = $this->withToken($t)->getJson('/api/v1/auth/candidate/matches?' . $q);
        $matches->assertStatus(200);
        $this->assertSame([$male->uuid], collect($matches->json('data'))->pluck('uuid')->all());

        $byCityId = $this->withToken($t)->getJson('/api/v1/auth/candidate/search?city_id=' . $cityId);
        $byCityId->assertStatus(200);
        $this->assertSame([$male->uuid], collect($byCityId->json('data'))->pluck('uuid')->all());

        Carbon::setTestNow();
    }

    public function test_discovery_filters_reject_inverted_age_range(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $viewer = $this->makeCandidate('filter-age@example.com');
        $this->withToken($this->tokenFor($viewer))
            ->getJson('/api/v1/auth/candidate/search?min_age=40&max_age=25')
            ->assertStatus(422);
    }

    public function test_discovery_treats_blank_id_filters_and_comma_lists_as_optional(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);
        $this->seed(DemoMasterDataSeeder::class);

        $viewer = $this->makeCandidate('blank-filters@example.com');
        $t = $this->tokenFor($viewer);

        $this->withToken($t)
            ->getJson('/api/v1/auth/candidate/favorites?perPage=10&education[]=&education[]=&occupation=')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $degreeIds = DB::table('degrees')
            ->orderBy('id')
            ->limit(2)
            ->pluck('id')
            ->map(static fn($id): int => (int) $id)
            ->all();
        $occupationId = (int) DB::table('occupations')->orderBy('id')->value('id');
        $educationQuery = implode(',', $degreeIds);

        $this->withToken($t)
            ->getJson("/api/v1/auth/candidate/search?education={$educationQuery}&occupation={$occupationId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_matches_reflects_verified_profile_and_favorite(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $viewer = $this->makeCandidate('match-viewer2@example.com');
        $this->subscribe($viewer, 'PARICHAY_FREE');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $matched = $this->makePublishedCandidate('match-target2@example.com');
        $this->subscribe($matched, 'PARICHAY_FREE');

        DB::table('user_verification_documents')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $matched->id,
            'document_type' => 'aadhaar',
            'document_number_masked' => 'XXXX',
            'verification_status' => 'approved',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('matches')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $viewer->id,
            'matched_user_id' => $matched->id,
            'match_score' => 70,
            'match_reason_json' => null,
            'match_status' => 'active',
            'generated_by' => 'system',
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $t = $this->tokenFor($viewer->fresh());
        $this->withToken($t)
            ->patchJson('/api/v1/auth/candidate/favorites/' . $matched->uuid, ['favorite' => true])
            ->assertStatus(200);

        $row = $this->withToken($t)->getJson('/api/v1/auth/candidate/matches')->json('data.0');
        $this->assertTrue($row['isVerified']);
        $this->assertSame('approved', $row['profileVerificationStatus']);
        $this->assertFalse($row['hasPremiumSubscription']);
        $this->assertTrue($row['isFavorite']);
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('discovery-test')->plainTextToken;
    }

    private function makeCandidate(string $email): User
    {
        $roleId = (int) Role::query()->where('name', 'candidate')->where('guard_name', 'web')->value('id');

        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Viewer',
            'last_name' => 'Test',
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => $roleId,
        ]);
        $user->assignRole('candidate');

        return $user;
    }

    private function makePublishedCandidate(string $email): User
    {
        $roleId = (int) Role::query()->where('name', 'candidate')->where('guard_name', 'web')->value('id');

        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Pub',
            'last_name' => 'Lished',
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => $roleId,
            'current_city' => 'Raipur',
            'current_state' => 'Chhattisgarh',
            'occupation' => 'Software',
            'date_of_birth' => '1992-06-15',
            'profile_status' => 'published',
            'published_at' => now(),
        ]);
        $user->assignRole('candidate');

        return $user;
    }

    private function subscribe(User $user, string $packageCode): void
    {
        $packageId = (int) DB::table('packages')->where('code', $packageCode)->value('id');
        $now = now();
        DB::table('subscriptions')->updateOrInsert(
            ['user_id' => $user->id, 'package_id' => $packageId],
            [
                'uuid' => (string) Str::uuid(),
                'subscription_status' => 'active',
                'started_at' => $now,
                'ends_at' => $now->copy()->addYear(),
                'auto_renew' => false,
                'renewal_source' => 'manual',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
