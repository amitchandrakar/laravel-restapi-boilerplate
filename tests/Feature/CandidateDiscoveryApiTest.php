<?php

declare(strict_types=1);
use App\Jobs\StartUserSessionJob;
use App\Models\ProfileDoNotShow;
use App\Models\ProfileSpamReport;
use App\Models\Role;
use App\Models\User;
use App\Services\PackagePermissionService;
use App\Services\UserActionLogService;
use App\Support\SanctumPlainTokenHasher;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

describe('candidate discovery API', function (): void {
    it('lists published candidates on browse with favorite metadata', function (): void {
        $viewer = makeCandidate('browse-viewer@example.com');
        subscribe($viewer, 'TALASH_BASIC');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $other = makePublishedCandidate('browse-other@example.com');

        $res = $this->withToken(tokenFor($viewer))->getJson('/api/v1/app/auth/candidate/search');
        $res->assertStatus(200)->assertJsonPath('success', true);
        $items = $res->json('data');
        expect($items)->toBeArray();
        $found = collect($items)->firstWhere('uuid', $other->uuid);
        expect($found)->not->toBeNull();
        expect($found)->toHaveKey('isFavorite');
        expect($found['isFavorite'])->toBeFalse();
        expect($found['occupation'])->toBe('Software');
        expect($found['profileVerificationStatus'])->toBe('not_submitted');

        $this->withToken(tokenFor($viewer))
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $other->uuid, ['favorite' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.favorite', true);

        $res2 = $this->withToken(tokenFor($viewer->fresh()))->getJson('/api/v1/app/auth/candidate/search');
        $found2 = collect($res2->json('data'))->firstWhere('uuid', $other->uuid);
        expect($found2['isFavorite'])->toBeTrue();
    });

    it('hides spam-reported candidates from the reporter browse and favorites until dismissed', function (): void {
        Notification::fake();

        $viewer = makeCandidate('spam-hide-viewer@example.com');
        subscribe($viewer, 'TALASH_BASIC');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $reported = makePublishedCandidate('spam-hide-reported@example.com');
        $other = makePublishedCandidate('spam-hide-other@example.com');
        $bystander = makeCandidate('spam-hide-bystander@example.com');
        subscribe($bystander, 'TALASH_BASIC');
        app(PackagePermissionService::class)->syncCandidatePermissions($bystander->fresh());

        $viewerToken = tokenFor($viewer);
        $this->withToken($viewerToken)
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $reported->uuid, ['favorite' => true])
            ->assertStatus(200);
        $this->withToken($viewerToken)
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $other->uuid, ['favorite' => true])
            ->assertStatus(200);

        $this->withToken($viewerToken)
            ->postJson('/api/v1/app/auth/candidate/' . $reported->uuid . '/report-spam', [
                'reason' => 'Suspicious messages asking for money.',
            ])
            ->assertStatus(201);

        $browse = $this->withToken($viewerToken)->getJson('/api/v1/app/auth/candidate/search');
        $browse->assertStatus(200);
        $browseUuids = collect($browse->json('data'))->pluck('uuid')->all();
        expect($browseUuids)->not->toContain((string) $reported->uuid);
        expect($browseUuids)->toContain((string) $other->uuid);

        $favorites = $this->withToken($viewerToken)->getJson('/api/v1/app/auth/candidate/favorites');
        $favorites->assertStatus(200);
        $favoriteUuids = collect($favorites->json('data'))->pluck('uuid')->all();
        expect($favoriteUuids)->not->toContain((string) $reported->uuid);
        expect($favoriteUuids)->toContain((string) $other->uuid);

        $bystanderBrowse = $this->withToken(tokenFor($bystander))->getJson('/api/v1/app/auth/candidate/search');
        $bystanderBrowse->assertStatus(200);
        expect(collect($bystanderBrowse->json('data'))->pluck('uuid')->all())->toContain((string) $reported->uuid);

        $report = ProfileSpamReport::query()
            ->where('reporter_user_id', $viewer->id)
            ->where('reported_user_id', $reported->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $admin = $this->createUserWithRole('admin', 'spam-hide-admin@example.com');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/moderation-reports/spam/' . $report->uuid . '/mark-not-spammer')
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'dismissed');

        $browseAfter = $this->withToken($viewerToken)->getJson('/api/v1/app/auth/candidate/search');
        $browseAfter->assertStatus(200);
        expect(collect($browseAfter->json('data'))->pluck('uuid')->all())->toContain((string) $reported->uuid);
    });

    it('hides muted candidates from the viewer browse until unmarked', function (): void {
        $viewer = makeCandidate('mute-hide-viewer@example.com');
        subscribe($viewer, 'TALASH_BASIC');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $hidden = makePublishedCandidate('mute-hide-target@example.com');
        $other = makePublishedCandidate('mute-hide-other@example.com');

        $viewerToken = tokenFor($viewer);
        $this->withToken($viewerToken)
            ->postJson('/api/v1/app/auth/candidate/' . $hidden->uuid . '/dont-show-again')
            ->assertStatus(201);

        $browse = $this->withToken($viewerToken)->getJson('/api/v1/app/auth/candidate/search');
        $browse->assertStatus(200);
        $browseUuids = collect($browse->json('data'))->pluck('uuid')->all();
        expect($browseUuids)->not->toContain((string) $hidden->uuid);
        expect($browseUuids)->toContain((string) $other->uuid);

        $admin = $this->createUserWithRole('admin', 'mute-hide-admin@example.com');
        $rowUuid = (string) ProfileDoNotShow::query()
            ->where('user_id', $viewer->id)
            ->where('hidden_user_id', $hidden->id)
            ->value('uuid');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/moderation-reports/dont-show-again')
            ->assertStatus(200)
            ->assertJsonPath('data.0.uuid', $rowUuid);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/moderation-reports/dont-show-again/' . $rowUuid . '/unmark')
            ->assertStatus(200);

        $browseAfter = $this->withToken($viewerToken)->getJson('/api/v1/app/auth/candidate/search');
        $browseAfter->assertStatus(200);
        expect(collect($browseAfter->json('data'))->pluck('uuid')->all())->toContain((string) $hidden->uuid);
    });

    it('forbids discovery when the viewer has no package permissions', function (): void {
        $viewer = makeCandidate('browse-no-package@example.com');
        $other = makePublishedCandidate('browse-other-nopkg@example.com');

        $t = tokenFor($viewer);
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/search')->assertStatus(403);
        $this->withToken($t)
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $other->uuid, ['favorite' => true])
            ->assertStatus(403);
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/favorites')->assertStatus(403);
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/matches')->assertStatus(403);
    });

    it('forbids discovery when identity is not verified even with package browse', function (): void {
        $viewer = makeCandidate('browse-unverified@example.com', false);
        subscribe($viewer, 'TALASH_BASIC');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());
        $other = makePublishedCandidate('browse-other-unverified@example.com');

        $t = tokenFor($viewer->fresh());
        $this->withToken($t)
            ->getJson('/api/v1/app/auth/candidate/search')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Complete identity verification before viewing other members.');
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/matches')->assertStatus(403);
        $this->withToken($t)
            ->getJson('/api/v1/app/auth/candidate/' . $other->uuid . '/profile-details')
            ->assertStatus(403);

        $this->approveCandidateKyc($viewer->fresh());
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/search')->assertStatus(200);
        $this->withToken($t)
            ->getJson('/api/v1/app/auth/candidate/' . $other->uuid . '/profile-details')
            ->assertStatus(200);
    });

    it('returns limited card fields for Parichay subscribers on browse', function (): void {
        $viewer = makeCandidate('browse-parichay@example.com');
        subscribe($viewer, 'PARICHAY_FREE');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());
        makePublishedCandidate('browse-target-parichay@example.com');

        $res = $this->withToken(tokenFor($viewer->fresh()))->getJson('/api/v1/app/auth/candidate/search');
        $res->assertStatus(200);
        $first = $res->json('data.0');
        expect($first)->toHaveKey('profileAccess', 'limited');
        expect($first)->not->toHaveKey('currentCity');
    });

    it('forbids favorites and matches for Parichay subscribers', function (): void {
        $viewer = makeCandidate('parichay-no-fav@example.com');
        subscribe($viewer, 'PARICHAY_FREE');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());
        $other = makePublishedCandidate('parichay-fav-target@example.com');

        $t = tokenFor($viewer->fresh());
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/favorites')->assertStatus(403);
        $this->withToken($t)
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $other->uuid, ['favorite' => true])
            ->assertStatus(403);
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/matches')->assertStatus(403);
    });

    it('returns forbidden on discovery routes for non-candidate accounts', function (): void {
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

        $t = tokenFor($admin);
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/search')->assertStatus(403);
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/favorites')->assertStatus(403);
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/matches')->assertStatus(403);
    });

    it('lets Talash subscribers list favorites and toggle them', function (): void {
        $viewer = makeCandidate('fav-talash@example.com');
        subscribe($viewer, 'TALASH_BASIC');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $target = makePublishedCandidate('fav-target@example.com');

        $t = tokenFor($viewer->fresh());
        $this->withToken($t)->getJson('/api/v1/app/auth/candidate/favorites')->assertStatus(200);
        $this->withToken($t)
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $target->uuid, ['favorite' => true])
            ->assertStatus(200);
    });

    it('returns every saved favorite from the favorites index', function (): void {
        $viewer = makeCandidate('fav-rishta@example.com');
        subscribe($viewer, 'RISHTA_PRO');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $a = makePublishedCandidate('fav-a@example.com');
        $b = makePublishedCandidate('fav-b@example.com');

        $t = tokenFor($viewer->fresh());
        $this->withToken($t)
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $a->uuid, ['favorite' => true])
            ->assertStatus(200);
        $this->withToken($t)
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $b->uuid, ['favorite' => true])
            ->assertStatus(200);

        $res = $this->withToken($t)->getJson('/api/v1/app/auth/candidate/favorites');
        $res->assertStatus(200)->assertJsonPath('success', true);
        $uuids = collect($res->json('data'))->pluck('uuid')->all();
        expect($uuids)->toContain($a->uuid);
        expect($uuids)->toContain($b->uuid);
    });

    it('returns synthesized match rows for browsing candidates', function (): void {
        $viewer = makeCandidate('match-viewer@example.com');
        subscribe($viewer, 'RISHTA_PRO');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $matched = makePublishedCandidate('match-target@example.com');
        subscribe($matched, 'RISHTA_PRO');

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

        $res = $this->withToken(tokenFor($viewer->fresh()))->getJson('/api/v1/app/auth/candidate/matches');
        $res->assertStatus(200)->assertJsonPath('success', true);
        $row = $res->json('data.0');
        expect($row['matchPercentage'])->toBe(88);
        expect($row['hasPremiumSubscription'])->toBeTrue();
        expect($row['uuid'])->toBe($matched->uuid);
        expect($row)->toHaveKey('matchReason');
        expect($row['matchReason']['summary'] ?? null)->toBe('Test reason');
    });

    it('applies the same advanced filters across search, favorites, and matches', function (): void {
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

        $viewer = makeCandidate('filter-viewer@example.com');
        subscribe($viewer, 'RISHTA_PRO');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());
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

        $t = tokenFor($viewer);
        $q = http_build_query([
            'gender' => 'male',
            'min_age' => 30,
            'max_age' => 40,
            'community' => [$surnameChandrakarId],
            'city' => 'Raipur',
            'education' => [$degreeMbaId],
            'occupation' => [$occEngineerId, $occTeacherId],
        ]);

        $search = $this->withToken($t)->getJson('/api/v1/app/auth/candidate/search?' . $q);
        $search->assertStatus(200);
        expect(collect($search->json('data'))->pluck('uuid')->all())->toBe([$male->uuid]);

        $this->withToken($t)
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $male->uuid, ['favorite' => true])
            ->assertStatus(200);
        $this->withToken($t)
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $female->uuid, ['favorite' => true])
            ->assertStatus(200);

        $fav = $this->withToken($t)->getJson('/api/v1/app/auth/candidate/favorites?' . $q);
        $fav->assertStatus(200);
        expect(collect($fav->json('data'))->pluck('uuid')->all())->toBe([$male->uuid]);

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

        $matches = $this->withToken($t)->getJson('/api/v1/app/auth/candidate/matches?' . $q);
        $matches->assertStatus(200);
        expect(collect($matches->json('data'))->pluck('uuid')->all())->toBe([$male->uuid]);

        $byCityId = $this->withToken($t)->getJson('/api/v1/app/auth/candidate/search?city_id=' . $cityId);
        $byCityId->assertStatus(200);
        expect(collect($byCityId->json('data'))->pluck('uuid')->all())->toBe([$male->uuid]);

        Carbon::setTestNow();
    });

    it('rejects discovery filters when minimum age exceeds maximum age', function (): void {
        $viewer = makeCandidate('filter-age@example.com');
        subscribe($viewer, 'PARICHAY_FREE');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());
        $this->withToken(tokenFor($viewer->fresh()))
            ->getJson('/api/v1/app/auth/candidate/search?min_age=40&max_age=25')
            ->assertStatus(422);
    });

    it('ignores blank id filters while still honoring comma-separated id lists', function (): void {
        $this->seed(DemoMasterDataSeeder::class);

        $viewer = makeCandidate('blank-filters@example.com');
        subscribe($viewer, 'RISHTA_PRO');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());
        $t = tokenFor($viewer->fresh());

        $this->withToken($t)
            ->getJson('/api/v1/app/auth/candidate/favorites?perPage=10&education[]=&education[]=&occupation=')
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
            ->getJson("/api/v1/app/auth/candidate/search?education={$educationQuery}&occupation={$occupationId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('shows verification badges and favorites on synthesized match payloads', function (): void {
        $viewer = makeCandidate('match-viewer2@example.com');
        subscribe($viewer, 'RISHTA_PRO');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        $matched = makePublishedCandidate('match-target2@example.com');
        subscribe($matched, 'PARICHAY_FREE');

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

        $t = tokenFor($viewer->fresh());
        $this->withToken($t)
            ->patchJson('/api/v1/app/auth/candidate/favorites/' . $matched->uuid, ['favorite' => true])
            ->assertStatus(200);

        $row = $this->withToken($t)->getJson('/api/v1/app/auth/candidate/matches')->json('data.0');
        expect($row['isVerified'])->toBeTrue();
        expect($row['profileVerificationStatus'])->toBe('approved');
        expect($row['hasPremiumSubscription'])->toBeFalse();
        expect($row['isFavorite'])->toBeTrue();
    });

    it('hard-filters browse and matches by viewer preferred gender', function (): void {
        Notification::fake();

        $viewer = makeCandidate('pref-gender-viewer@example.com');
        subscribe($viewer, 'RISHTA_PRO');
        app(PackagePermissionService::class)->syncCandidatePermissions($viewer->fresh());

        DB::table('user_partner_preferences')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $viewer->id,
            'preferred_gender' => 'Female',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $female = makePublishedCandidate('pref-gender-female@example.com');
        $female->update(['gender' => 'female', 'first_name' => 'Female']);
        $male = makePublishedCandidate('pref-gender-male@example.com');
        $male->update(['gender' => 'male', 'first_name' => 'Male']);

        DB::table('matches')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'user_id' => $viewer->id,
                'matched_user_id' => $female->id,
                'match_score' => 90,
                'match_reason_json' => null,
                'match_status' => 'active',
                'generated_by' => 'system',
                'generated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
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
            ],
        ]);

        $token = tokenFor($viewer->fresh());

        $browse = $this->withToken($token)->getJson('/api/v1/app/auth/candidate/search?gender=male');
        $browse->assertStatus(200);
        $browseUuids = collect($browse->json('data'))->pluck('uuid')->all();
        expect($browseUuids)->toContain((string) $female->uuid);
        expect($browseUuids)->not->toContain((string) $male->uuid);

        $matches = $this->withToken($token)->getJson('/api/v1/app/auth/candidate/matches');
        $matches->assertStatus(200);
        $matchUuids = collect($matches->json('data'))->pluck('uuid')->all();
        expect($matchUuids)->toContain((string) $female->uuid);
        expect($matchUuids)->not->toContain((string) $male->uuid);
    });
});
function tokenFor(User $user): string
{
    $token = $user->createToken('discovery-test')->plainTextToken;
    $hash = SanctumPlainTokenHasher::hashPlainTextToken($token);
    (new StartUserSessionJob($user->id, $hash, null, '127.0.0.1', 'discovery-test', 'test-device'))->handle(
        app(UserActionLogService::class)
    );

    return $token;
}
function makeCandidate(string $email, bool $kycApproved = true): User
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

    if ($kycApproved) {
        test()->approveCandidateKyc($user);
    }

    return $user;
}
function makePublishedCandidate(string $email): User
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
function subscribe(User $user, string $packageCode): void
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
