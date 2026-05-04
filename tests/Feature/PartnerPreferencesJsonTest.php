<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PartnerPreferencesJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_partner_preferences_arrays_are_stored_as_json(): void
    {
        $this->seed(RbacSeeder::class);

        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Testland',
            'iso2' => 'TL',
            'iso3' => 'TST',
            'phone_code' => '+999',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $stateId = DB::table('states')->insertGetId([
            'country_id' => $countryId,
            'name' => 'Test State',
            'code' => 'TS',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cityA = DB::table('cities')->insertGetId([
            'state_id' => $stateId,
            'name' => 'City A',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cityB = DB::table('cities')->insertGetId([
            'state_id' => $stateId,
            'name' => 'City B',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $langId = DB::table('languages')->insertGetId([
            'name' => 'English',
            'code' => 'en',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $degree6 = DB::table('degrees')->insertGetId([
            'name' => 'Degree Six',
            'degree_type' => 'undergraduate',
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $degree13 = DB::table('degrees')->insertGetId([
            'name' => 'Degree Thirteen',
            'degree_type' => 'postgraduate',
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $surname1 = DB::table('surnames')->insertGetId([
            'name' => 'SurnameOne',
            'language_id' => $langId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $surname2 = DB::table('surnames')->insertGetId([
            'name' => 'SurnameTwo',
            'language_id' => $langId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var User $candidate */
        $candidate = User::query()->create([
            'first_name' => 'Partner',
            'last_name' => 'Prefs',
            'email' => 'partner-json-test@example.com',
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => (int) Role::query()->where('name', 'candidate')->value('id'),
        ]);
        $candidate->assignRole('candidate');

        $this->actingAs($candidate, 'sanctum')
            ->patchJson('/api/v1/auth/candidate/profile/partner-preferences', [
                'preferred_min_age' => 18,
                'preferred_max_age' => 25,
                'preferred_gender' => 'Male',
                'preferred_caste' => 'Any',
                'preferred_diet' => 'Non-Vegetarian',
                'preferred_smoking' => 'Never',
                'preferred_drinking' => 'Never',
                'preferred_income_min' => '50000',
                'preferred_degree_ids' => [$degree6, $degree13],
                'preferred_location_ids' => [$cityA, $cityB],
                'preferred_community_ids' => [$surname1, $surname2],
            ])
            ->assertStatus(200);

        $row = DB::table('user_partner_preferences')->where('user_id', $candidate->id)->first();
        $this->assertNotNull($row);

        $decode = static function (mixed $raw): array {
            if (is_array($raw)) {
                return $raw;
            }
            if (is_string($raw)) {
                $j = json_decode($raw, true);

                return is_array($j) ? $j : [];
            }

            return [];
        };

        $this->assertSame([$degree6, $degree13], $decode($row->preferred_degree_ids));
        $this->assertSame([$cityA, $cityB], $decode($row->preferred_location_ids));
        $this->assertSame([$surname1, $surname2], $decode($row->preferred_community_ids));
        $this->assertSame('Any', $row->preferred_caste);
        $this->assertEquals(50000.0, (float) $row->preferred_income_min);
    }
}
