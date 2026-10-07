<?php

declare(strict_types=1);

use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;

it('persists rashi, nakshatra, and birth place from the admin horoscope section patch', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(ChhattisgarhMasterGeoSeeder::class);

    $admin = $this->createUserWithRole('admin', 'admin-horoscope-section@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-horoscope-section@example.com');

    $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
    $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
    $cityId = (int) DB::table('cities')->where('state_id', $stateId)->value('id');
    $countryName = (string) DB::table('countries')->where('id', $countryId)->value('name');
    $stateName = (string) DB::table('states')->where('id', $stateId)->value('name');
    $cityName = (string) DB::table('cities')->where('id', $cityId)->value('name');

    expect($countryId)->toBeGreaterThan(0);
    expect($stateId)->toBeGreaterThan(0);
    expect($cityId)->toBeGreaterThan(0);

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/admin/candidates/{$candidate->uuid}/sections/horoscope", [
            'date_of_birth' => '1992-03-14',
            'time_of_birth' => '06:30',
            'zodiac_sign' => 'Pisces',
            'rashi' => 'Meena',
            'nakshatra' => 'Revati',
            'place_of_birth_line' => 'Sample Village, Sample District, Raipur, Chhattisgarh, India',
            'place_of_birth_country' => $countryName,
            'place_of_birth_state' => $stateName,
            'place_of_birth_city' => $cityName,
            'place_of_birth_district' => 'Sample District',
            'place_of_birth_village' => 'Sample Village',
            'birth_country_id' => $countryId,
            'birth_state_id' => $stateId,
            'birth_city_id' => $cityId,
        ])
        ->assertStatus(200);

    $candidate->refresh();
    expect($candidate->rashi)->toBe('Meena');
    expect($candidate->nakshatra)->toBe('Revati');
    expect($candidate->birth_country_id)->toBe($countryId);
    expect($candidate->birth_state_id)->toBe($stateId);
    expect($candidate->birth_city_id)->toBe($cityId);
    expect($candidate->place_of_birth_country)->toBe($countryName);
    expect($candidate->place_of_birth_state)->toBe($stateName);
    expect($candidate->place_of_birth_city)->toBe($cityName);
    expect($candidate->place_of_birth_district)->toBe('Sample District');
    expect($candidate->place_of_birth_village)->toBe('Sample Village');

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/admin/candidates/{$candidate->uuid}/profile-details")
        ->assertStatus(200)
        ->assertJsonPath('data.sections.horoscopeDetails.rashi', 'Meena')
        ->assertJsonPath('data.sections.horoscopeDetails.nakshatra', 'Revati')
        ->assertJsonPath('data.sections.horoscopeDetails.birthPlace.country', $countryName)
        ->assertJsonPath('data.sections.horoscopeDetails.birthPlace.state', $stateName)
        ->assertJsonPath('data.sections.horoscopeDetails.birthPlace.city', $cityName)
        ->assertJsonPath('data.sections.horoscopeDetails.birthPlace.district', 'Sample District')
        ->assertJsonPath('data.sections.horoscopeDetails.birthPlace.village', 'Sample Village');
});
