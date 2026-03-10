<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder
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
                'from_station_id' => 'ST001',
                'to_station_id' => 'ST002',
                'sequence' => 1,
                'train_id' => 1,
            ],
            [
                'id' => 2,
                'from_station_id' => 'ST002',
                'to_station_id' => 'ST003',
                'sequence' => 2,
                'train_id' => 1,
            ],
            [
                'id' => 3,
                'from_station_id' => 'ST003',
                'to_station_id' => 'ST004',
                'sequence' => 3,
                'train_id' => 1,
            ],
            [
                'id' => 4,
                'from_station_id' => 'ST004',
                'to_station_id' => 'ST005',
                'sequence' => 4,
                'train_id' => 1,
            ],
            [
                'id' => 5,
                'from_station_id' => 'ST005',
                'to_station_id' => 'ST006',
                'sequence' => 5,
                'train_id' => 1,
            ],
            [
                'id' => 6,
                'from_station_id' => 'ST006',
                'to_station_id' => 'ST007',
                'sequence' => 1,
                'train_id' => 2,
            ],
            [
                'id' => 7,
                'from_station_id' => 'ST007',
                'to_station_id' => 'ST008',
                'sequence' => 2,
                'train_id' => 2,
            ],
            [
                'id' => 8,
                'from_station_id' => 'ST008',
                'to_station_id' => 'ST009',
                'sequence' => 3,
                'train_id' => 2,
            ],
            [
                'id' => 9,
                'from_station_id' => 'ST009',
                'to_station_id' => 'ST010',
                'sequence' => 4,
                'train_id' => 2,
            ],
            [
                'id' => 10,
                'from_station_id' => 'ST001',
                'to_station_id' => 'ST003',
                'sequence' => 1,
                'train_id' => 3,
            ],
            [
                'id' => 11,
                'from_station_id' => 'ST003',
                'to_station_id' => 'ST005',
                'sequence' => 2,
                'train_id' => 3,
            ],
            [
                'id' => 12,
                'from_station_id' => 'ST005',
                'to_station_id' => 'ST007',
                'sequence' => 3,
                'train_id' => 3,
            ],
            [
                'id' => 13,
                'from_station_id' => 'ST007',
                'to_station_id' => 'ST009',
                'sequence' => 4,
                'train_id' => 3,
            ],
            [
                'id' => 14,
                'from_station_id' => 'ST002',
                'to_station_id' => 'ST004',
                'sequence' => 5,
                'train_id' => 3,
            ],
            [
                'id' => 15,
                'from_station_id' => 'ST004',
                'to_station_id' => 'ST006',
                'sequence' => 6,
                'train_id' => 3,
            ],
            [
                'id' => 16,
                'from_station_id' => 'ST007',
                'to_station_id' => 'ST008',
                'sequence' => 7,
                'train_id' => 3,
            ],
            [
                'id' => 17,
                'from_station_id' => 'ST008',
                'to_station_id' => 'ST009',
                'sequence' => 8,
                'train_id' => 3,
            ],
            [
                'id' => 18,
                'from_station_id' => 'ST009',
                'to_station_id' => 'ST010',
                'sequence' => 9,
                'train_id' => 3,
            ],
        ];

        DB::table('schedule')->insert($dataTables);
    }
}
