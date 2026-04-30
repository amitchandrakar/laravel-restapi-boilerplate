<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fetch_all_report_endpoints(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'reports-admin@example.com');
        $candidate = $this->createUserWithRole('candidate', 'reports-candidate-1@example.com', 'Chandrakar');
        $reviewer = $this->createUserWithRole('reviewer', 'reports-reviewer@example.com', 'Verma');

        User::query()->where('id', $candidate->id)->update([
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
    }

    public function test_candidate_cannot_access_report_endpoints(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'reports-candidate-2@example.com');

        $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/candidates/area')->assertStatus(403);
        $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/candidates/surname')->assertStatus(403);
        $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/candidates/education')->assertStatus(403);
        $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/active-users')->assertStatus(403);
        $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/user-activities')->assertStatus(403);
        $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/reports/team-activities')->assertStatus(403);
    }

    public function test_area_group_by_validation_is_enforced(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'reports-admin-validation@example.com');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/reports/candidates/area?groupBy=country')
            ->assertStatus(422);
    }

    private function createUserWithRole(string $role, string $email, string $lastName = 'User'): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => $lastName,
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}

