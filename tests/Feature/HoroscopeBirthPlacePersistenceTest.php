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

class HoroscopeBirthPlacePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_horoscope_patch_persists_birth_geo_ids_when_chain_is_valid(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(ChhattisgarhMasterGeoSeeder::class);

        $admin = $this->createUserWithRole('admin', 'admin-horoscope-geo@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-horoscope-geo@example.com');

        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
        $cityId = (int) DB::table('cities')->where('state_id', $stateId)->value('id');
        $districtId = (int) DB::table('districts')->where('state_id', $stateId)->value('id');
        $villageId = (int) DB::table('villages')->where('district_id', $districtId)->value('id');

        $this->assertGreaterThan(0, $countryId);
        $this->assertGreaterThan(0, $stateId);
        $this->assertGreaterThan(0, $cityId);
        $this->assertGreaterThan(0, $districtId);
        $this->assertGreaterThan(0, $villageId);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/horoscope', [
                'date_of_birth' => '1995-08-07',
                'time_of_birth' => '11:00',
                'zodiac_sign' => 'Scorpio',
                'place_of_birth_line' => 'Raipur, Chhattisgarh, India',
                'birth_country_id' => $countryId,
                'birth_state_id' => $stateId,
                'birth_city_id' => $cityId,
                'birth_district_id' => $districtId,
                'birth_village_id' => $villageId,
            ])
            ->assertStatus(200);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/candidates/' . $candidate->uuid)
            ->assertStatus(200)
            ->assertJsonPath('data.sections.horoscopeDetails.birthCountryId', $countryId)
            ->assertJsonPath('data.sections.horoscopeDetails.birthVillageId', $villageId);

        $candidate->refresh();
        $this->assertSame('1995-08-07', $candidate->date_of_birth?->format('Y-m-d'));
        $this->assertSame($countryId, $candidate->birth_country_id);
        $this->assertSame($stateId, $candidate->birth_state_id);
        $this->assertSame($cityId, $candidate->birth_city_id);
        $this->assertSame($districtId, $candidate->birth_district_id);
        $this->assertSame($villageId, $candidate->birth_village_id);
        $this->assertSame('Scorpio', $candidate->zodiac_sign);
        $this->assertSame('Raipur, Chhattisgarh, India', $candidate->place_of_birth_line);
    }

    public function test_horoscope_rejects_village_not_in_given_district(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(ChhattisgarhMasterGeoSeeder::class);

        $admin = $this->createUserWithRole('admin', 'admin-horoscope-bad@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-horoscope-bad@example.com');

        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
        $districtIds = DB::table('districts')->where('state_id', $stateId)->orderBy('id')->pluck('id');
        $this->assertGreaterThanOrEqual(2, $districtIds->count(), 'Need two districts for mismatch test');

        $districtA = (int) $districtIds[0];
        $districtB = (int) $districtIds[1];
        $villageInA = (int) DB::table('villages')->where('district_id', $districtA)->value('id');
        $this->assertGreaterThan(0, $villageInA);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/horoscope', [
                'birth_country_id' => $countryId,
                'birth_state_id' => $stateId,
                'birth_district_id' => $districtB,
                'birth_village_id' => $villageInA,
            ])
            ->assertStatus(422);
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
