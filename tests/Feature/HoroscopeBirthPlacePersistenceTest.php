<?php

declare(strict_types=1);
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;

it('persists birth geography IDs from the admin horoscope patch when the chain is valid', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(ChhattisgarhMasterGeoSeeder::class);

    $admin = $this->createUserWithRole('admin', 'admin-horoscope-geo@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-horoscope-geo@example.com');

    $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
    $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
    $cityId = (int) DB::table('cities')->where('state_id', $stateId)->value('id');

    expect($countryId)->toBeGreaterThan(0);
    expect($stateId)->toBeGreaterThan(0);
    expect($cityId)->toBeGreaterThan(0);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/horoscope', [
            'date_of_birth' => '1995-08-07',
            'time_of_birth' => '11:00',
            'zodiac_sign' => 'Scorpio',
            'place_of_birth_line' => 'Raipur, Chhattisgarh, India',
            'birth_country_id' => $countryId,
            'birth_state_id' => $stateId,
            'birth_city_id' => $cityId,
        ])
        ->assertStatus(200);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/candidates/' . $candidate->uuid)
        ->assertStatus(200)
        ->assertJsonPath('data.sections.horoscopeDetails.birthCountryId', $countryId)
        ->assertJsonPath('data.sections.horoscopeDetails.birthCityId', $cityId);

    $candidate->refresh();
    expect($candidate->date_of_birth?->format('Y-m-d'))->toBe('1995-08-07');
    expect($candidate->birth_country_id)->toBe($countryId);
    expect($candidate->birth_state_id)->toBe($stateId);
    expect($candidate->birth_city_id)->toBe($cityId);
    expect($candidate->zodiac_sign)->toBe('Scorpio');
    expect($candidate->place_of_birth_line)->toBe('Raipur, Chhattisgarh, India');
});

it('rejects a horoscope birth city outside the submitted state', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(ChhattisgarhMasterGeoSeeder::class);

    $admin = $this->createUserWithRole('admin', 'admin-horoscope-bad@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-horoscope-bad@example.com');

    $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
    $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
    expect($countryId)->toBeGreaterThan(0);
    expect($stateId)->toBeGreaterThan(0);

    $otherStateId = (int) DB::table('states')->insertGetId([
        'country_id' => $countryId,
        'name' => 'Other State',
        'code' => 'OS',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $badCityId = (int) DB::table('cities')->insertGetId([
        'state_id' => $otherStateId,
        'name' => 'Other City',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/horoscope', [
            'birth_country_id' => $countryId,
            'birth_state_id' => $stateId,
            'birth_city_id' => $badCityId,
        ])
        ->assertStatus(422);
});
