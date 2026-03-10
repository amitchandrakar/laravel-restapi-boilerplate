<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimesSeeder extends Seeder
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
                'time' => '4:00 AM - 4:30 AM',
                'sort' => 1,
                'night_time' => 0,
            ],
            [
                'id' => 2,
                'time' => '4:30 AM - 5:00 AM',
                'sort' => 3,
                'night_time' => 0,
            ],
            [
                'id' => 3,
                'time' => '5:00 AM - 5:30 AM',
                'sort' => 5,
                'night_time' => 0,
            ],
            [
                'id' => 4,
                'time' => '5:30 AM - 6:00 AM',
                'sort' => 7,
                'night_time' => 0,
            ],
            [
                'id' => 5,
                'time' => '6:00 AM - 6:30 AM',
                'sort' => 9,
                'night_time' => 0,
            ],
            [
                'id' => 6,
                'time' => '6:30 AM - 7:00 AM',
                'sort' => 11,
                'night_time' => 0,
            ],
            [
                'id' => 7,
                'time' => '7:00 AM - 7:30 AM',
                'sort' => 13,
                'night_time' => 0,
            ],
            [
                'id' => 8,
                'time' => '7:30 AM - 8:00 AM',
                'sort' => 15,
                'night_time' => 0,
            ],
            [
                'id' => 9,
                'time' => '8:00 AM - 8:30 AM',
                'sort' => 17,
                'night_time' => 0,
            ],
            [
                'id' => 10,
                'time' => '8:30 AM - 9:00 AM',
                'sort' => 19,
                'night_time' => 0,
            ],
            [
                'id' => 11,
                'time' => '9:00 AM - 9:30 AM',
                'sort' => 21,
                'night_time' => 0,
            ],
            [
                'id' => 12,
                'time' => '9:30 AM - 10:00 AM',
                'sort' => 23,
                'night_time' => 0,
            ],
            [
                'id' => 13,
                'time' => '10:00 AM - 10:30 AM',
                'sort' => 25,
                'night_time' => 0,
            ],
            [
                'id' => 14,
                'time' => '10:30 AM - 11:00 AM',
                'sort' => 27,
                'night_time' => 0,
            ],
            [
                'id' => 15,
                'time' => '11:00 AM - 11:30 AM',
                'sort' => 29,
                'night_time' => 0,
            ],
            [
                'id' => 16,
                'time' => '11:30 AM - 12:00 PM',
                'sort' => 31,
                'night_time' => 0,
            ],
            [
                'id' => 17,
                'time' => '12:00 PM - 12:30 PM',
                'sort' => 33,
                'night_time' => 0,
            ],
            [
                'id' => 18,
                'time' => '12:30 PM - 1:00 PM',
                'sort' => 35,
                'night_time' => 0,
            ],
            [
                'id' => 19,
                'time' => '1:00 PM - 1:30 PM',
                'sort' => 37,
                'night_time' => 0,
            ],
            [
                'id' => 20,
                'time' => '1:30 PM - 2:00 PM',
                'sort' => 39,
                'night_time' => 0,
            ],
            [
                'id' => 21,
                'time' => '2:00 PM - 2:30 PM',
                'sort' => 41,
                'night_time' => 0,
            ],
            [
                'id' => 22,
                'time' => '2:30 PM - 3:00 PM',
                'sort' => 43,
                'night_time' => 0,
            ],
            [
                'id' => 23,
                'time' => '3:00 PM - 3:30 PM',
                'sort' => 45,
                'night_time' => 0,
            ],
            [
                'id' => 24,
                'time' => '3:30 PM - 4:00 PM',
                'sort' => 47,
                'night_time' => 0,
            ],
            [
                'id' => 25,
                'time' => '4:00 PM - 4:30 PM',
                'sort' => 49,
                'night_time' => 0,
            ],
            [
                'id' => 26,
                'time' => '4:30 PM - 5:00 PM',
                'sort' => 51,
                'night_time' => 0,
            ],
            [
                'id' => 27,
                'time' => '5:00 PM - 5:30 PM',
                'sort' => 53,
                'night_time' => 0,
            ],
            [
                'id' => 28,
                'time' => '5:30 PM - 6:00 PM',
                'sort' => 55,
                'night_time' => 0,
            ],
            [
                'id' => 29,
                'time' => '6:00 PM - 6:30 PM',
                'sort' => 57,
                'night_time' => 0,
            ],
            [
                'id' => 30,
                'time' => '6:30 PM - 7:00 PM',
                'sort' => 59,
                'night_time' => 0,
            ],
            [
                'id' => 31,
                'time' => '7:00 PM - 7:30 PM',
                'sort' => 61,
                'night_time' => 0,
            ],
            [
                'id' => 32,
                'time' => '7:30 PM - 8:00 PM',
                'sort' => 63,
                'night_time' => 0,
            ],
            [
                'id' => 33,
                'time' => '8:00 PM - 8:30 PM',
                'sort' => 65,
                'night_time' => 0,
            ],
            [
                'id' => 34,
                'time' => '8:30 PM - 9:00 PM',
                'sort' => 67,
                'night_time' => 0,
            ],
            [
                'id' => 35,
                'time' => '9:00 PM - 9:30 PM',
                'sort' => 69,
                'night_time' => 0,
            ],
            [
                'id' => 36,
                'time' => '9:30 PM - 10:00 PM',
                'sort' => 71,
                'night_time' => 0,
            ],
            [
                'id' => 37,
                'time' => '10:00 PM - 10:30 PM',
                'sort' => 73,
                'night_time' => 0,
            ],
            [
                'id' => 38,
                'time' => '10:30 PM - 11:00 PM',
                'sort' => 75,
                'night_time' => 0,
            ],
            [
                'id' => 39,
                'time' => '11:00 PM - 11:30 PM',
                'sort' => 77,
                'night_time' => 0,
            ],
            [
                'id' => 40,
                'time' => '11:30 PM - 12:00 AM',
                'sort' => 79,
                'night_time' => 0,
            ],
            [
                'id' => 41,
                'time' => '12:00 AM - 12:30 AM',
                'sort' => 81,
                'night_time' => 0,
            ],
            [
                'id' => 42,
                'time' => '12:30 AM - 1:00 AM',
                'sort' => 83,
                'night_time' => 0,
            ],
            [
                'id' => 43,
                'time' => '1:00 AM - 1:30 AM',
                'sort' => 85,
                'night_time' => 0,
            ],
            [
                'id' => 44,
                'time' => '4:15 AM - 4:45 AM',
                'sort' => 2,
                'night_time' => 0,
            ],
            [
                'id' => 45,
                'time' => '4:45 AM - 5:15 AM',
                'sort' => 4,
                'night_time' => 0,
            ],
            [
                'id' => 46,
                'time' => '5:15 AM - 5:45 AM',
                'sort' => 6,
                'night_time' => 0,
            ],
            [
                'id' => 47,
                'time' => '5:45 AM - 6:15 AM',
                'sort' => 8,
                'night_time' => 0,
            ],
            [
                'id' => 48,
                'time' => '6:15 AM - 6:45 AM',
                'sort' => 10,
                'night_time' => 0,
            ],
            [
                'id' => 49,
                'time' => '6:45 AM - 7:15 AM',
                'sort' => 12,
                'night_time' => 0,
            ],
            [
                'id' => 50,
                'time' => '7:15 AM - 7:45 AM',
                'sort' => 14,
                'night_time' => 0,
            ],
            [
                'id' => 51,
                'time' => '7:45 AM - 8:15 AM',
                'sort' => 16,
                'night_time' => 0,
            ],
            [
                'id' => 52,
                'time' => '8:15 AM - 8:45 AM',
                'sort' => 18,
                'night_time' => 0,
            ],
            [
                'id' => 53,
                'time' => '8:45 AM - 9:15 AM',
                'sort' => 20,
                'night_time' => 0,
            ],
            [
                'id' => 54,
                'time' => '9:15 AM - 9:45 AM',
                'sort' => 22,
                'night_time' => 0,
            ],
            [
                'id' => 55,
                'time' => '9:45 AM - 10:15 AM',
                'sort' => 24,
                'night_time' => 0,
            ],
            [
                'id' => 56,
                'time' => '10:15 AM - 10:45 AM',
                'sort' => 26,
                'night_time' => 0,
            ],
            [
                'id' => 57,
                'time' => '10:45 AM - 11:15 AM',
                'sort' => 28,
                'night_time' => 0,
            ],
            [
                'id' => 58,
                'time' => '11:15 AM - 11:45 AM',
                'sort' => 30,
                'night_time' => 0,
            ],
            [
                'id' => 59,
                'time' => '11:45 AM - 12:15 PM',
                'sort' => 32,
                'night_time' => 0,
            ],
            [
                'id' => 60,
                'time' => '12:15 PM - 12:45 PM',
                'sort' => 34,
                'night_time' => 0,
            ],
            [
                'id' => 61,
                'time' => '12:45 PM - 1:15 PM',
                'sort' => 36,
                'night_time' => 0,
            ],
            [
                'id' => 62,
                'time' => '1:15 PM - 1:45 PM',
                'sort' => 38,
                'night_time' => 0,
            ],
            [
                'id' => 63,
                'time' => '1:45 PM - 2:15 PM',
                'sort' => 40,
                'night_time' => 0,
            ],
            [
                'id' => 64,
                'time' => '2:15 PM - 2:45 PM',
                'sort' => 42,
                'night_time' => 0,
            ],
            [
                'id' => 65,
                'time' => '2:45 PM - 3:15 PM',
                'sort' => 44,
                'night_time' => 0,
            ],
            [
                'id' => 66,
                'time' => '3:15 PM - 3:45 PM',
                'sort' => 46,
                'night_time' => 0,
            ],
            [
                'id' => 67,
                'time' => '3:45 PM - 4:15 PM',
                'sort' => 48,
                'night_time' => 0,
            ],
            [
                'id' => 68,
                'time' => '4:15 PM - 4:45 PM',
                'sort' => 50,
                'night_time' => 0,
            ],
            [
                'id' => 69,
                'time' => '4:45 PM - 5:15 PM',
                'sort' => 52,
                'night_time' => 0,
            ],
            [
                'id' => 70,
                'time' => '5:15 PM - 5:45 PM',
                'sort' => 54,
                'night_time' => 0,
            ],
            [
                'id' => 71,
                'time' => '5:45 PM - 6:15 PM',
                'sort' => 56,
                'night_time' => 0,
            ],
            [
                'id' => 72,
                'time' => '6:15 PM - 6:45 PM',
                'sort' => 58,
                'night_time' => 0,
            ],
            [
                'id' => 73,
                'time' => '6:45 PM - 7:15 PM',
                'sort' => 60,
                'night_time' => 0,
            ],
            [
                'id' => 74,
                'time' => '7:15 PM - 7:45 PM',
                'sort' => 62,
                'night_time' => 0,
            ],
            [
                'id' => 75,
                'time' => '7:45 PM - 8:15 PM',
                'sort' => 64,
                'night_time' => 0,
            ],
            [
                'id' => 76,
                'time' => '8:15 PM - 8:45 PM',
                'sort' => 66,
                'night_time' => 0,
            ],
            [
                'id' => 77,
                'time' => '8:45 PM - 9:15 PM',
                'sort' => 68,
                'night_time' => 0,
            ],
            [
                'id' => 78,
                'time' => '9:15 PM - 9:45 PM',
                'sort' => 70,
                'night_time' => 0,
            ],
            [
                'id' => 79,
                'time' => '9:45 PM - 10:15 PM',
                'sort' => 72,
                'night_time' => 0,
            ],
            [
                'id' => 80,
                'time' => '10:15 PM - 10:45 PM',
                'sort' => 74,
                'night_time' => 0,
            ],
            [
                'id' => 81,
                'time' => '10:45 PM - 11:15 PM',
                'sort' => 76,
                'night_time' => 0,
            ],
            [
                'id' => 82,
                'time' => '11:15 PM - 11:45 PM',
                'sort' => 78,
                'night_time' => 0,
            ],
            [
                'id' => 83,
                'time' => '11:45 PM - 12:15 AM',
                'sort' => 80,
                'night_time' => 0,
            ],
            [
                'id' => 84,
                'time' => '12:15 AM - 12:45 AM',
                'sort' => 82,
                'night_time' => 0,
            ],
            [
                'id' => 85,
                'time' => '12:45 AM - 1:15 AM',
                'sort' => 84,
                'night_time' => 0,
            ],
            [
                'id' => 86,
                'time' => '1:15 AM - 1:45 AM',
                'sort' => 86,
                'night_time' => 0,
            ],
        ];

        DB::table('times')->insert($dataTables);
    }
}
