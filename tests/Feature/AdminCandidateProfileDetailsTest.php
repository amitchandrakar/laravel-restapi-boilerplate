<?php

declare(strict_types=1);

use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('resolves birth geography in profile details and hides raw identifier fields', function (): void {
    $this->seed(DemoMasterDataSeeder::class);
    $this->seed(ChhattisgarhMasterGeoSeeder::class);

    $candidate = $this->createUserWithRole('candidate', 'candidate-profile-details@example.com');

    $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
    $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
    $cityId = (int) DB::table('cities')->where('state_id', $stateId)->value('id');

    $this->actingAs($candidate, 'sanctum')
        ->patchJson('/api/v1/app/auth/candidate/profile/horoscope', [
            'date_of_birth' => '1995-08-07',
            'birth_country_id' => $countryId,
            'birth_state_id' => $stateId,
            'birth_city_id' => $cityId,
        ])
        ->assertStatus(200);

    $countryName = (string) DB::table('countries')->where('id', $countryId)->value('name');

    $res = $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/app/auth/candidate/profile/details')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.sections.horoscopeDetails.birthPlace.country', $countryName);

    $horoscope = $res->json('data.sections.horoscopeDetails');
    expect($horoscope)->toBeArray();
    expect($horoscope)->not->toHaveKey('birthCountryId');
    expect($horoscope)->not->toHaveKey('birthStateId');
    expect($horoscope)->toHaveKey('birthPlace');
});

it('returns not found for profile details when the UUID is unknown', function (): void {
    $candidate = $this->createUserWithRole('candidate', 'candidate-profile-details-404@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/app/auth/candidate/' . (string) Str::uuid() . '/profile-details')
        ->assertStatus(404);
});

it('lets a candidate load their own profile details', function (): void {
    $candidate = $this->createUserWithRole('candidate', 'candidate-own-profile-details@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/app/auth/candidate/profile/details')
        ->assertStatus(200)
        ->assertJsonPath('data.uuid', $candidate->uuid);
});

it('lets a candidate view another candidate profile through profile details', function (): void {
    $a = $this->createUserWithRole('candidate', 'candidate-a-profile-details@example.com');
    $b = $this->createUserWithRole('candidate', 'candidate-b-profile-details@example.com');

    $this->actingAs($a, 'sanctum')
        ->getJson('/api/v1/app/auth/candidate/' . $b->uuid . '/profile-details')
        ->assertStatus(200)
        ->assertJsonPath('data.uuid', $b->uuid);
});
