<?php

declare(strict_types=1);
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;

it('persists maternal geography IDs from admin family-roots updates', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(ChhattisgarhMasterGeoSeeder::class);

    $admin = $this->createUserWithRole('admin', 'admin-maternal@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-maternal@example.com');

    $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
    $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
    $cityId = (int) DB::table('cities')->where('state_id', $stateId)->value('id');

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/location-family-roots', [
            'maternal_country_id' => $countryId,
            'maternal_state_id' => $stateId,
            'maternal_city_id' => $cityId,
        ])
        ->assertStatus(200);

    $candidate->refresh();
    expect($candidate->maternal_country_id)->toBe($countryId);
    expect($candidate->maternal_city_id)->toBe($cityId);
});

it('still accepts legacy maternal geography keys during family-roots updates', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(ChhattisgarhMasterGeoSeeder::class);

    $admin = $this->createUserWithRole('admin', 'admin-maternal-legacy@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-maternal-legacy@example.com');

    $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
    $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
    $cityId = (int) DB::table('cities')->where('state_id', $stateId)->value('id');

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/location-family-roots', [
            'maternal_country' => (string) $countryId,
            'maternal_state' => (string) $stateId,
            'maternal_city' => (string) $cityId,
        ])
        ->assertStatus(200);

    $candidate->refresh();
    expect($candidate->maternal_country_id)->toBe($countryId);
    expect($candidate->maternal_city_id)->toBe($cityId);
});
