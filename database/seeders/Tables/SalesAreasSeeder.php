<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesAreasSeeder extends Seeder
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
                'id' => 1,
                'name' => 'Austin North',
                'cafenum' => 62,
            ],
            [
                'id' => 2,
                'name' => 'Houston Southwest',
                'cafenum' => 49,
            ],
            [
                'id' => 3,
                'name' => 'Medical Center',
                'cafenum' => 33,
            ],
            [
                'id' => 4,
                'name' => 'Medical Center',
                'cafenum' => 56,
            ],
            [
                'id' => 5,
                'name' => 'Oak Brook',
                'cafenum' => 51,
            ],
            [
                'id' => 6,
                'name' => 'Chicago North',
                'cafenum' => 55,
            ],
            [
                'id' => 7,
                'name' => 'Houston Northwest',
                'cafenum' => 58,
            ],
            [
                'id' => 8,
                'name' => 'Las Colinas',
                'cafenum' => 57,
            ],
            [
                'id' => 9,
                'name' => 'San Antonio - East',
                'cafenum' => 65,
            ],
            [
                'id' => 10,
                'name' => 'Chicago Loop',
                'cafenum' => 15,
            ],
            [
                'id' => 11,
                'name' => 'Austin Central',
                'cafenum' => 60,
            ],
            [
                'id' => 12,
                'name' => 'Schaumburg',
                'cafenum' => 67,
            ],
            [
                'id' => 13,
                'name' => 'Houston Galleria Post Oak',
                'cafenum' => 4,
            ],
            [
                'id' => 14,
                'name' => 'San Antonio - West',
                'cafenum' => 64,
            ],
            [
                'id' => 15,
                'name' => 'Laguna Hills',
                'cafenum' => 72,
            ],
            [
                'id' => 16,
                'name' => 'The Woodlands',
                'cafenum' => 69,
            ],
            [
                'id' => 17,
                'name' => 'Houston Downtown - A',
                'cafenum' => 2,
            ],
            [
                'id' => 18,
                'name' => 'Houston Downtown - A',
                'cafenum' => 48,
            ],
            [
                'id' => 19,
                'name' => 'Chicago West Loop',
                'cafenum' => 59,
            ],
            [
                'id' => 20,
                'name' => 'Houston West',
                'cafenum' => 47,
            ],
            [
                'id' => 21,
                'name' => 'Houston Downtown - A',
                'cafenum' => 36,
            ],
            [
                'id' => 22,
                'name' => 'Dallas North',
                'cafenum' => 52,
            ],
            [
                'id' => 23,
                'name' => 'Fort Worth West',
                'cafenum' => 32,
            ],
            [
                'id' => 24,
                'name' => 'Fort Worth East',
                'cafenum' => 53,
            ],
            [
                'id' => 25,
                'name' => 'Houston Downtown - B',
                'cafenum' => 7,
            ],
            [
                'id' => 26,
                'name' => 'Houston Downtown - B',
                'cafenum' => 18,
            ],
            [
                'id' => 27,
                'name' => 'Orange',
                'cafenum' => 73,
            ],
            [
                'id' => 28,
                'name' => 'Dallas Downtown',
                'cafenum' => 6,
            ],
            [
                'id' => 29,
                'name' => 'Dallas Central',
                'cafenum' => 71,
            ],
            [
                'id' => 30,
                'name' => 'Santa Ana',
                'cafenum' => 68,
            ],
            [
                'id' => 31,
                'name' => 'Houston North',
                'cafenum' => 14,
            ],
            [
                'id' => 32,
                'name' => 'Greenway Plaza',
                'cafenum' => 31,
            ],
            [
                'id' => 33,
                'name' => 'Dallas NE',
                'cafenum' => 70,
            ],
            [
                'id' => 34,
                'name' => 'Greenway Plaza',
                'cafenum' => 1,
            ],
            [
                'id' => 35,
                'name' => 'Greenway Plaza',
                'cafenum' => 8,
            ],
            [
                'id' => 36,
                'name' => 'Chicago South',
                'cafenum' => 74,
            ],
            [
                'id' => 37,
                'name' => 'Chicago West',
                'cafenum' => 75,
            ],
            [
                'id' => 38,
                'name' => 'Medical Center South',
                'cafenum' => 77,
            ],
            [
                'id' => 39,
                'name' => 'NE LA',
                'cafenum' => 76,
            ],
            [
                'id' => 40,
                'name' => 'Central LA',
                'cafenum' => 78,
            ],
            [
                'id' => 41,
                'name' => 'Burbank',
                'cafenum' => 79,
            ],
            [
                'id' => 42,
                'name' => 'Austin South',
                'cafenum' => 102,
            ],
            [
                'id' => 43,
                'name' => 'Orange County 4',
                'cafenum' => 101,
            ],
            [
                'id' => 44,
                'name' => 'Dallas Frisco',
                'cafenum' => 105,
            ],
            [
                'id' => 45,
                'name' => 'Las Colinas North',
                'cafenum' => 104,
            ],
            [
                'id' => 46,
                'name' => 'Anaheim',
                'cafenum' => 73,
            ],
        ];

        DB::table('sales_areas')->insert($dataTables);
    }
}
