<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminCandidateProfileDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_details_resolves_birth_geo_and_omits_raw_ids(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(DemoMasterDataSeeder::class);
        $this->seed(ChhattisgarhMasterGeoSeeder::class);

        $admin = $this->createUserWithRole('admin', 'admin-profile-details@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-profile-details@example.com');

        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
        $cityId = (int) DB::table('cities')->where('state_id', $stateId)->value('id');
        $districtId = (int) DB::table('districts')->where('state_id', $stateId)->value('id');
        $villageId = (int) DB::table('villages')->where('district_id', $districtId)->value('id');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/horoscope', [
                'date_of_birth' => '1995-08-07',
                'birth_country_id' => $countryId,
                'birth_state_id' => $stateId,
                'birth_city_id' => $cityId,
                'birth_district_id' => $districtId,
                'birth_village_id' => $villageId,
            ])
            ->assertStatus(200);

        $countryName = (string) DB::table('countries')->where('id', $countryId)->value('name');

        $res = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/candidates/' . $candidate->uuid . '/profile-details')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sections.horoscopeDetails.birthPlace.country', $countryName);

        $horoscope = $res->json('data.sections.horoscopeDetails');
        $this->assertIsArray($horoscope);
        $this->assertArrayNotHasKey('birthCountryId', $horoscope);
        $this->assertArrayNotHasKey('birthStateId', $horoscope);
        $this->assertArrayHasKey('birthPlace', $horoscope);
    }

    public function test_profile_details_returns_404_for_unknown_uuid(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-profile-details-404@example.com');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/candidates/' . (string) Str::uuid() . '/profile-details')
            ->assertStatus(404);
    }

    public function test_profile_details_candidate_can_view_own_profile(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'candidate-own-profile-details@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->getJson('/api/v1/admin/candidates/' . $candidate->uuid . '/profile-details')
            ->assertStatus(200)
            ->assertJsonPath('data.uuid', $candidate->uuid);
    }

    public function test_profile_details_candidate_can_view_another_candidate_profile(): void
    {
        $this->seed(RbacSeeder::class);
        $a = $this->createUserWithRole('candidate', 'candidate-a-profile-details@example.com');
        $b = $this->createUserWithRole('candidate', 'candidate-b-profile-details@example.com');

        $this->actingAs($a, 'sanctum')
            ->getJson('/api/v1/admin/candidates/' . $b->uuid . '/profile-details')
            ->assertStatus(200)
            ->assertJsonPath('data.uuid', $b->uuid);
    }

    private function createUserWithRole(string $role, string $email): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => (int) Role::query()->where('name', $role)->where('guard_name', 'web')->value('id'),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
