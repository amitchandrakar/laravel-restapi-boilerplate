<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPackageCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_view_packages(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-list@example.com');
        $package = $this->createPackage('LIST_PLAN');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/packages?perPage=10')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.code', 'LIST_PLAN')
            ->assertJsonPath('data.0.featurePermissions', []);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/packages/' . $package->id)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'LIST_PLAN')
            ->assertJsonPath('data.featurePermissions', []);
    }

    public function test_admin_can_update_package_and_switch_default(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-update@example.com');
        $defaultPackage = $this->createPackage('DEFAULT_PLAN', true);
        $target = $this->createPackage('TARGET_PLAN', false);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/packages/' . $target->id, [
                'name' => 'Updated Target',
                'duration_unit' => 'month',
                'monthly_price' => 399,
                'yearly_price' => 3999,
                'is_default_registration' => true,
                'is_popular' => true,
                'permission_ids' => [$this->permissionIdByName('candidate.send_contact_requests')],
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Target')
            ->assertJsonPath('data.durationUnit', 'month')
            ->assertJsonPath('data.isDefaultRegistration', true)
            ->assertJsonPath('data.isPopular', true)
            ->assertJsonPath('data.featurePermissions.0.name', 'candidate.send_contact_requests');

        $this->assertDatabaseHas('packages', [
            'id' => $target->id,
            'is_default_registration' => true,
            'is_popular' => true,
        ]);
        $this->assertDatabaseHas('packages', [
            'id' => $defaultPackage->id,
            'is_default_registration' => false,
        ]);
    }

    public function test_admin_can_soft_delete_package(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-delete@example.com');
        $package = $this->createPackage('DELETE_PLAN');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/packages/' . $package->id)
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('packages', ['id' => $package->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/packages')
            ->assertStatus(200)
            ->assertJsonMissing(['code' => 'DELETE_PLAN']);
    }

    public function test_delete_non_existing_package_returns_not_found(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-missing-delete@example.com');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/packages/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_candidate_cannot_access_package_crud_endpoints(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'candidate-crud@example.com');
        $package = $this->createPackage('FORBIDDEN_PLAN');

        $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/packages')->assertStatus(403);
        $this->actingAs($candidate, 'sanctum')
            ->getJson('/api/v1/admin/packages/' . $package->id)
            ->assertStatus(403);
        $this->actingAs($candidate, 'sanctum')
            ->patchJson('/api/v1/admin/packages/' . $package->id, ['name' => 'Nope'])
            ->assertStatus(403);
        $this->actingAs($candidate, 'sanctum')
            ->deleteJson('/api/v1/admin/packages/' . $package->id)
            ->assertStatus(403);
    }

    public function test_update_validation_applies_business_rules(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-rules@example.com');
        $packageOne = $this->createPackage('VALIDATION_A');
        $packageTwo = $this->createPackage('VALIDATION_B');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/packages/' . $packageTwo->id, [
                'code' => 'VALIDATION_A',
                'duration_unit' => 'day',
                'monthly_price' => -1,
            ])
            ->assertStatus(422);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/packages/' . $packageTwo->id, [
                'code' => 'VALIDATION_C',
                'duration_unit' => 'year',
                'monthly_price' => 10,
                'yearly_price' => 120,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.code', 'VALIDATION_C');

        $this->assertDatabaseHas('packages', ['id' => $packageOne->id, 'code' => 'VALIDATION_A']);
    }

    public function test_cannot_create_two_active_packages_with_same_name(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-name-unique@example.com');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/packages', [
                'name' => 'Same Name',
                'code' => 'NAME_ONE',
                'duration_unit' => 'year',
                'monthly_price' => 100,
                'yearly_price' => 1000,
            ])
            ->assertStatus(201);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/packages', [
                'name' => 'Same Name',
                'code' => 'NAME_TWO',
                'duration_unit' => 'year',
                'monthly_price' => 120,
                'yearly_price' => 1200,
            ])
            ->assertStatus(422);
    }

    public function test_create_package_with_candidate_permission_ids_and_fetch_options(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-perm-options@example.com');

        $permIds = [
            $this->permissionIdByName('candidate.browse_profiles.full'),
            $this->permissionIdByName('candidate.view_full_profile_details'),
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/packages', [
                'name' => 'Permission Plan',
                'code' => 'PERMISSION_PLAN',
                'duration_unit' => 'year',
                'monthly_price' => 100,
                'yearly_price' => 1000,
                'permission_ids' => $permIds,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.featurePermissions.0.name', 'candidate.browse_profiles.full')
            ->assertJsonPath('data.featurePermissions.1.name', 'candidate.view_full_profile_details');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/packages/permission-options')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_non_candidate_permission_id_is_rejected_for_package_payload(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-invalid-perm@example.com');
        $nonCandidatePermissionId = $this->permissionIdByName('admin.dashboard.view');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/packages', [
                'name' => 'Invalid Permission Plan',
                'code' => 'INVALID_PERMISSION_PLAN',
                'duration_unit' => 'year',
                'monthly_price' => 100,
                'yearly_price' => 1000,
                'permission_ids' => [$nonCandidatePermissionId],
            ])
            ->assertStatus(422);
    }

    public function test_updating_package_permissions_syncs_active_candidate_subscribers_immediately(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-sync@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-sync@example.com');
        $package = $this->createPackage('SYNC_PLAN');

        DB::table('subscriptions')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $candidate->id,
            'package_id' => $package->id,
            'subscription_status' => 'active',
            'started_at' => now(),
            'ends_at' => now()->copy()->addYear(),
            'auto_renew' => false,
            'renewal_source' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse($candidate->fresh()->hasPermissionTo('candidate.send_contact_requests'));

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/packages/' . $package->id, [
                'permission_ids' => [$this->permissionIdByName('candidate.send_contact_requests')],
            ])
            ->assertStatus(200);

        $this->assertTrue($candidate->fresh()->hasPermissionTo('candidate.send_contact_requests'));
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

    private function createPackage(string $code, bool $isDefaultRegistration = false): Package
    {
        /** @var Package $package */
        $package = Package::query()->create([
            'name' => $code . ' Name',
            'code' => $code,
            'description' => 'Test package',
            'duration_unit' => 'year',
            'monthly_price' => 100,
            'yearly_price' => 1000,
            'price' => 1000,
            'discounted_price' => null,
            'currency' => 'INR',
            'is_active' => true,
            'is_default_registration' => $isDefaultRegistration,
            'is_popular' => false,
            'sort_order' => 1,
        ]);

        return $package;
    }

    private function permissionIdByName(string $name): int
    {
        return (int) \App\Models\Permission::query()->where('name', $name)->value('id');
    }
}
