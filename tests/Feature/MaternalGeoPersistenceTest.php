<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaternalGeoPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_location_family_roots_persists_maternal_geo_ids(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(ChhattisgarhMasterGeoSeeder::class);

        $admin = $this->createUserWithRole('admin', 'admin-maternal@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-maternal@example.com');

        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
        $cityId = (int) DB::table('cities')->where('state_id', $stateId)->value('id');
        $districtId = (int) DB::table('districts')->where('state_id', $stateId)->value('id');
        $villageId = (int) DB::table('villages')->where('district_id', $districtId)->value('id');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/location-family-roots', [
                'maternal_country_id' => $countryId,
                'maternal_state_id' => $stateId,
                'maternal_city_id' => $cityId,
                'maternal_district_id' => $districtId,
                'maternal_village_id' => $villageId,
            ])
            ->assertStatus(200);

        $candidate->refresh();
        $this->assertSame($countryId, $candidate->maternal_country_id);
        $this->assertSame($villageId, $candidate->maternal_village_id);
    }

    public function test_admin_location_family_roots_accepts_legacy_maternal_keys(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(ChhattisgarhMasterGeoSeeder::class);

        $admin = $this->createUserWithRole('admin', 'admin-maternal-legacy@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-maternal-legacy@example.com');

        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
        $cityId = (int) DB::table('cities')->where('state_id', $stateId)->value('id');
        $districtId = (int) DB::table('districts')->where('state_id', $stateId)->value('id');
        $villageId = (int) DB::table('villages')->where('district_id', $districtId)->value('id');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/location-family-roots', [
                'maternal_country' => (string) $countryId,
                'maternal_state' => (string) $stateId,
                'maternal_city' => (string) $cityId,
                'maternal_district' => (string) $districtId,
                'maternal_village' => (string) $villageId,
            ])
            ->assertStatus(200);

        $candidate->refresh();
        $this->assertSame($countryId, $candidate->maternal_country_id);
        $this->assertSame($villageId, $candidate->maternal_village_id);
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
            'role_id' => (int) Role::query()->where('name', $role)->value('id'),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
