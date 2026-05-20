<?php

declare(strict_types=1);
use App\Models\Package;
use App\Models\Permission;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

describe('admin package management', function (): void {
    it('allows admins to list and view packages', function (): void {
        $admin = $this->createUserWithRole('admin', 'admin-list@example.com');
        $package = packageCrudMakePackage('LIST_PLAN');

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
    });

    it('allows admins to update packages and migrate the registration default flag', function () {
        $admin = $this->createUserWithRole('admin', 'admin-update@example.com');
        $defaultPackage = packageCrudMakePackage('DEFAULT_PLAN', true);
        $target = packageCrudMakePackage('TARGET_PLAN', false);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/packages/' . $target->id, [
                'name' => 'Updated Target',
                'duration_unit' => 'month',
                'monthly_price' => 399,
                'yearly_price' => 3999,
                'is_default_registration' => true,
                'is_popular' => true,
                'permission_ids' => [packageCrudPermissionIdByName('candidate.send_contact_requests')],
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
    });

    it('allows admins to soft-delete packages', function () {
        $admin = $this->createUserWithRole('admin', 'admin-delete@example.com');
        $package = packageCrudMakePackage('DELETE_PLAN');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/packages/' . $package->id)
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('packages', ['id' => $package->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/packages')
            ->assertStatus(200)
            ->assertJsonMissing(['code' => 'DELETE_PLAN']);
    });

    it('returns not found when deleting an unknown package id', function () {
        $admin = $this->createUserWithRole('admin', 'admin-missing-delete@example.com');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/packages/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    });

    it('returns forbidden when a candidate calls package admin endpoints', function () {
        $candidate = $this->createUserWithRole('candidate', 'candidate-crud@example.com');
        $package = packageCrudMakePackage('FORBIDDEN_PLAN');

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
    });

    it('runs package update validation rules for codes, durations, and prices', function () {
        $admin = $this->createUserWithRole('admin', 'admin-rules@example.com');
        $packageOne = packageCrudMakePackage('VALIDATION_A');
        $packageTwo = packageCrudMakePackage('VALIDATION_B');

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
    });

    it('blocks creating two concurrently active packages with identical names', function () {
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
    });

    it('stores candidate permission identifiers on packages and exposes permission options', function () {
        $admin = $this->createUserWithRole('admin', 'admin-perm-options@example.com');

        $permIds = [
            packageCrudPermissionIdByName('candidate.browse_profiles.full'),
            packageCrudPermissionIdByName('candidate.view_full_profile_details'),
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
    });

    it('rejects package payloads containing non candidate-scoped permissions', function () {
        $admin = $this->createUserWithRole('admin', 'admin-invalid-perm@example.com');
        $nonCandidatePermissionId = packageCrudPermissionIdByName('admin.dashboard.view');

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
    });

    it('updates live candidate subscriptions when admins change packaged permissions', function () {
        $admin = $this->createUserWithRole('admin', 'admin-sync@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-sync@example.com');
        $package = packageCrudMakePackage('SYNC_PLAN');

        DB::table('subscriptions')->insert([
            'uuid' => (string) Str::uuid(),
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

        expect($candidate->fresh()->hasPermissionTo('candidate.send_contact_requests'))->toBeFalse();

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/packages/' . $package->id, [
                'permission_ids' => [packageCrudPermissionIdByName('candidate.send_contact_requests')],
            ])
            ->assertStatus(200);

        expect($candidate->fresh()->hasPermissionTo('candidate.send_contact_requests'))->toBeTrue();
    });
});
function packageCrudMakePackage(string $code, bool $isDefaultRegistration = false): Package
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
function packageCrudPermissionIdByName(string $name): int
{
    return (int) Permission::query()->where('name', $name)->value('id');
}
