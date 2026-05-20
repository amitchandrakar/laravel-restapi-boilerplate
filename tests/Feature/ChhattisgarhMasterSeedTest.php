<?php

declare(strict_types=1);
use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\MasterDegreesOccupationsSeeder;
use Illuminate\Support\Facades\DB;

it('seeds Chhattisgarh geography reference data from the master dataset', function () {
    $this->seed(ChhattisgarhMasterGeoSeeder::class);

    $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
    $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');

    expect($countryId)->toBeGreaterThan(0);
    expect($stateId)->toBeGreaterThan(0);
    expect(DB::table('cities')->where('state_id', $stateId)->count())->toBeGreaterThanOrEqual(33);
});

it('seeds academic degrees and occupations idempotently', function () {
    $this->seed(MasterDegreesOccupationsSeeder::class);
    $degreeCount = DB::table('degrees')->count();
    $occupationCount = DB::table('occupations')->count();

    expect($degreeCount)->toBeGreaterThanOrEqual(20);
    expect($occupationCount)->toBeGreaterThanOrEqual(20);

    $this->seed(MasterDegreesOccupationsSeeder::class);

    expect(DB::table('degrees')->count())->toBe($degreeCount);
    expect(DB::table('occupations')->count())->toBe($occupationCount);
});
