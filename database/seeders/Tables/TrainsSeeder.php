<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainsSeeder extends Seeder
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
                'from_station' => 'A',
                'to_station' => 'B',
                'train_id' => 1,
            ],
            [
                'id' => 2,
                'from_station' => 'A',
                'to_station' => 'B',
                'train_id' => 2,
            ],
            [
                'id' => 3,
                'from_station' => 'B',
                'to_station' => 'C',
                'train_id' => 1,
            ],
            [
                'id' => 4,
                'from_station' => 'C',
                'to_station' => 'D',
                'train_id' => 1,
            ],
        ];

        DB::table('trains')->insert($dataTables);
    }
}
