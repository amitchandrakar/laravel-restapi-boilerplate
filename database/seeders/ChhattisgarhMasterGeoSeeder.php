<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChhattisgarhMasterGeoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('countries')->updateOrInsert(
            ['iso2' => 'IN'],
            [
                'name' => 'India',
                'iso3' => 'IND',
                'phone_code' => '+91',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        if ($countryId === 0) {
            return;
        }

        DB::table('states')->updateOrInsert(
            ['country_id' => $countryId, 'code' => 'CG'],
            [
                'name' => 'Chhattisgarh',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
        if ($stateId === 0) {
            return;
        }

        /** @var array<int, string> $districts */
        $districts = json_decode(
            (string) file_get_contents(database_path('seeders/data/chhattisgarh_districts.json')),
            true
        );
        foreach ($districts as $districtName) {
            DB::table('districts')->updateOrInsert(
                ['state_id' => $stateId, 'name' => $districtName],
                ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        /** @var array<int, string> $cities */
        $cities = json_decode((string) file_get_contents(database_path('seeders/data/chhattisgarh_cities.json')), true);
        foreach ($cities as $cityName) {
            DB::table('cities')->updateOrInsert(
                ['state_id' => $stateId, 'name' => $cityName],
                ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $districtIdMap = DB::table('districts')
            ->where('state_id', $stateId)
            ->pluck('id', 'name')
            ->mapWithKeys(static fn($id, $name): array => [(string) $name => (int) $id])
            ->all();

        /** @var array<int, array{district:string,village:string}> $villages */
        $villages = json_decode(
            (string) file_get_contents(database_path('seeders/data/chhattisgarh_villages.json')),
            true
        );
        foreach ($villages as $row) {
            $districtId = $districtIdMap[$row['district']] ?? 0;
            if ($districtId === 0) {
                continue;
            }
            DB::table('villages')->updateOrInsert(
                ['district_id' => $districtId, 'name' => $row['village']],
                ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
