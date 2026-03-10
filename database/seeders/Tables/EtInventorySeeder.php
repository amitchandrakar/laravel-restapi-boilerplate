<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EtInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Command :
         * artisan seed:generate --table-mode --all-tables --limit=500
         */
        $dataTables = [
            [
                'inventory_id' => 12,
                'cafe_id' => 68,
                'month' => 9,
                'year' => 2014,
                'food' => '7176.2100',
                'beer' => '1076.4800',
                'wine' => 0,
            ],
            [
                'inventory_id' => 13,
                'cafe_id' => 68,
                'month' => 10,
                'year' => 2014,
                'food' => 7279,
                'beer' => 2172,
                'wine' => 0,
            ],
            [
                'inventory_id' => 14,
                'cafe_id' => 68,
                'month' => 11,
                'year' => 2014,
                'food' => 5136,
                'beer' => 1652,
                'wine' => 0,
            ],
            [
                'inventory_id' => 15,
                'cafe_id' => 68,
                'month' => 12,
                'year' => 2014,
                'food' => 5448,
                'beer' => 1290,
                'wine' => 1791,
            ],
            [
                'inventory_id' => 16,
                'cafe_id' => 68,
                'month' => 1,
                'year' => 2015,
                'food' => 7023,
                'beer' => 3151,
                'wine' => 2274,
            ],
            [
                'inventory_id' => 17,
                'cafe_id' => 68,
                'month' => 2,
                'year' => 2014,
                'food' => 6517,
                'beer' => 2484,
                'wine' => 1912,
            ],
            [
                'inventory_id' => 18,
                'cafe_id' => 68,
                'month' => 2,
                'year' => 2015,
                'food' => 6517,
                'beer' => 2484,
                'wine' => 1912,
            ],
            [
                'inventory_id' => 19,
                'cafe_id' => 68,
                'month' => 3,
                'year' => 2015,
                'food' => 4406,
                'beer' => 1579,
                'wine' => 1655,
            ],
            [
                'inventory_id' => 20,
                'cafe_id' => 68,
                'month' => 4,
                'year' => 2015,
                'food' => 5771,
                'beer' => 2242,
                'wine' => 1467,
            ],
            [
                'inventory_id' => 21,
                'cafe_id' => 68,
                'month' => 5,
                'year' => 2015,
                'food' => 3395,
                'beer' => 1948,
                'wine' => 1218,
            ],
            [
                'inventory_id' => 22,
                'cafe_id' => 68,
                'month' => 6,
                'year' => 2015,
                'food' => '5276.9200',
                'beer' => '1296.0200',
                'wine' => 926,
            ],
            [
                'inventory_id' => 23,
                'cafe_id' => 68,
                'month' => 7,
                'year' => 2015,
                'food' => 4930,
                'beer' => 1460,
                'wine' => 559,
            ],
            [
                'inventory_id' => 24,
                'cafe_id' => 68,
                'month' => 8,
                'year' => 2015,
                'food' => 4827,
                'beer' => 1395,
                'wine' => 228,
            ],
            [
                'inventory_id' => 25,
                'cafe_id' => 68,
                'month' => 1,
                'year' => 2014,
                'food' => 0,
                'beer' => 0,
                'wine' => 0,
            ],
            [
                'inventory_id' => 26,
                'cafe_id' => 68,
                'month' => 9,
                'year' => 2015,
                'food' => 3962,
                'beer' => 1820,
                'wine' => 143,
            ],
            [
                'inventory_id' => 27,
                'cafe_id' => 68,
                'month' => 10,
                'year' => 2015,
                'food' => 4255,
                'beer' => 1615,
                'wine' => 195,
            ],
            [
                'inventory_id' => 28,
                'cafe_id' => 68,
                'month' => 11,
                'year' => 2015,
                'food' => 4098,
                'beer' => 1154,
                'wine' => 214,
            ],
            [
                'inventory_id' => 29,
                'cafe_id' => 68,
                'month' => 12,
                'year' => 2015,
                'food' => 5285,
                'beer' => 1550,
                'wine' => 332,
            ],
            [
                'inventory_id' => 30,
                'cafe_id' => 68,
                'month' => 1,
                'year' => 2016,
                'food' => 3002,
                'beer' => 881,
                'wine' => 141,
            ],
        ];

        DB::table('et_inventory')->insert($dataTables);
    }
}
