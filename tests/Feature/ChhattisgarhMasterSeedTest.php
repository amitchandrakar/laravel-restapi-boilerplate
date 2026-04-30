<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\MasterDegreesOccupationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChhattisgarhMasterSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_chhattisgarh_geo_master_data_is_seeded(): void
    {
        $this->seed(ChhattisgarhMasterGeoSeeder::class);

        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');

        $this->assertGreaterThan(0, $countryId);
        $this->assertGreaterThan(0, $stateId);
        $this->assertSame(33, DB::table('districts')->where('state_id', $stateId)->count());
        $this->assertGreaterThanOrEqual(33, DB::table('cities')->where('state_id', $stateId)->count());
        $this->assertGreaterThanOrEqual(
            33,
            DB::table('villages')
                ->whereIn('district_id', DB::table('districts')->where('state_id', $stateId)->pluck('id'))
                ->count()
        );
    }

    public function test_degrees_and_occupations_are_seeded_and_idempotent(): void
    {
        $this->seed(MasterDegreesOccupationsSeeder::class);
        $degreeCount = DB::table('degrees')->count();
        $occupationCount = DB::table('occupations')->count();

        $this->assertGreaterThanOrEqual(20, $degreeCount);
        $this->assertGreaterThanOrEqual(20, $occupationCount);

        $this->seed(MasterDegreesOccupationsSeeder::class);

        $this->assertSame($degreeCount, DB::table('degrees')->count());
        $this->assertSame($occupationCount, DB::table('occupations')->count());
    }
}
