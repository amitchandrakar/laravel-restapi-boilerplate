<?php

declare(strict_types=1);
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('allows admins to call every report endpoint', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'reports-admin@example.com');
    $candidate = $this->createUserWithRole('candidate', 'reports-candidate-1@example.com', 'Chandrakar');
    $reviewer = $this->createUserWithRole('reviewer', 'reports-reviewer@example.com', 'Verma');

    User::query()
        ->where('id', $candidate->id)
        ->update([
            'current_state' => 'Chhattisgarh',
            'current_district' => 'Durg',
            'current_city' => 'Bhilai',
            'current_village' => 'Jamul',
        ]);

    DB::table('user_education_details')->insert([
        'uuid' => (string) Str::uuid(),
        'user_id' => $candidate->id,
        'education_type' => 'graduation',
        'field_of_study' => 'Engineering',
        'institution_name' => 'Test College',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('user_sessions')->insert([
        'uuid' => (string) Str::uuid(),
        'user_id' => $candidate->id,
        'session_token_hash' => hash('sha256', 'token-1'),
        'refresh_token_hash' => hash('sha256', 'refresh-1'),
        'login_at' => now()->subDay(),
        'expires_at' => now()->addDays(7),
        'is_active' => true,
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('user_activity_logs')->insert([
        'uuid' => (string) Str::uuid(),
        'user_id' => $candidate->id,
        'activity_type' => 'auth.login',
        'activity_source' => 'api_v1_auth',
        'metadata_json' => json_encode(['ok' => true], JSON_THROW_ON_ERROR),
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    DB::table('audit_logs')->insert([
        'uuid' => (string) Str::uuid(),
        'actor_user_id' => $reviewer->id,
        'entity_type' => 'users',
        'entity_id' => $candidate->id,
        'action' => 'update',
        'old_values_json' => null,
        'new_values_json' => json_encode(['status' => 'active'], JSON_THROW_ON_ERROR),
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/reports/candidates/area?groupBy=district')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/reports/candidates/surname')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/reports/candidates/education')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/reports/active-users')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/reports/user-activities?activityType=auth.login')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/reports/team-activities?action=update')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/dashboard/stats')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'totals' => [
                    'candidates',
                    'newCandidates7Days',
                    'newCandidates30Days',
                    'premiumMembers',
                    'freeMembers',
                    'revenueDemo',
                    'teams',
                    'totalUsers',
                    'totalPayments',
                    'totalReferrals',
                    'reportsGeneratedTotal',
                    'reportsGenerated7Days',
                    'reportsGenerated30Days',
                    'pendingApproval',
                    'approvedToday',
                    'activeMatchesTotal',
                    'profileViews7Days',
                    'contactActionsTotal',
                    'successStoriesLanding',
                ],
                'genderSplit',
                'candidatesByAge',
                'teamsByLocation',
                'candidatesByLocationTop10',
                'topSubCastes',
                'revenue' => ['monthOnMonth', 'yearOnYear', 'bySubscriptionType'],
                'registrations' => ['monthOnMonth', 'yearOnYear'],
                'activeSubscriptions' => ['monthOnMonth', 'yearOnYear'],
            ],
        ]);
});

it('returns forbidden when a candidate calls report endpoints', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'reports-candidate-2@example.com');

    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/candidates/area')->assertStatus(403);
    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/candidates/surname')->assertStatus(403);
    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/candidates/education')->assertStatus(403);
    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/active-users')->assertStatus(403);
    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/user-activities')->assertStatus(403);
    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/team-activities')->assertStatus(403);
    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/dashboard/stats')->assertStatus(403);
});

it('enforces reporting area group-by validation rules', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'reports-admin-validation@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/reports/candidates/area?groupBy=country')
        ->assertStatus(422);
});
