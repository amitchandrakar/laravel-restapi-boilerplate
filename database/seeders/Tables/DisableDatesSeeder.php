<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DisableDatesSeeder extends Seeder
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
                'id' => 5,
                'admin_id' => 175889,
                'disabled_dates' => '2022-09-05',
                'created_at' => '2022-08-16 13:51:44',
                'updated_at' => '2022-08-16 13:51:44',
                'deleted_at' => null,
            ],
            [
                'id' => 6,
                'admin_id' => 175889,
                'disabled_dates' => '2022-11-24',
                'created_at' => '2022-11-21 13:08:12',
                'updated_at' => '2022-11-21 13:08:12',
                'deleted_at' => null,
            ],
            [
                'id' => 7,
                'admin_id' => 175889,
                'disabled_dates' => '2022-11-25',
                'created_at' => '2022-11-21 13:08:19',
                'updated_at' => '2022-11-21 13:08:19',
                'deleted_at' => null,
            ],
            [
                'id' => 8,
                'admin_id' => 175889,
                'disabled_dates' => '2023-01-02',
                'created_at' => '2022-12-17 13:58:49',
                'updated_at' => '2022-12-17 13:58:49',
                'deleted_at' => null,
            ],
            [
                'id' => 9,
                'admin_id' => 175889,
                'disabled_dates' => '2022-12-26',
                'created_at' => '2022-12-19 13:41:08',
                'updated_at' => '2022-12-19 13:41:08',
                'deleted_at' => null,
            ],
            [
                'id' => 11,
                'admin_id' => 175889,
                'disabled_dates' => '2023-07-04',
                'created_at' => '2023-06-20 16:12:57',
                'updated_at' => '2023-06-20 16:12:57',
                'deleted_at' => null,
            ],
            [
                'id' => 12,
                'admin_id' => 175889,
                'disabled_dates' => '2023-09-04',
                'created_at' => '2023-08-29 12:27:59',
                'updated_at' => '2023-08-29 12:27:59',
                'deleted_at' => null,
            ],
            [
                'id' => 13,
                'admin_id' => 175889,
                'disabled_dates' => '2023-11-23',
                'created_at' => '2023-10-18 19:25:45',
                'updated_at' => '2023-10-18 19:25:45',
                'deleted_at' => null,
            ],
            [
                'id' => 14,
                'admin_id' => 175889,
                'disabled_dates' => '2023-11-24',
                'created_at' => '2023-10-18 19:25:56',
                'updated_at' => '2023-10-18 19:25:56',
                'deleted_at' => null,
            ],
            [
                'id' => 15,
                'admin_id' => 175889,
                'disabled_dates' => '2023-12-25',
                'created_at' => '2023-10-18 19:26:06',
                'updated_at' => '2023-10-18 19:26:06',
                'deleted_at' => null,
            ],
            [
                'id' => 16,
                'admin_id' => 175889,
                'disabled_dates' => '2024-01-01',
                'created_at' => '2023-10-18 19:26:15',
                'updated_at' => '2023-10-18 19:26:15',
                'deleted_at' => null,
            ],
            [
                'id' => 17,
                'admin_id' => 175889,
                'disabled_dates' => '2024-11-28',
                'created_at' => '2024-04-29 17:30:36',
                'updated_at' => '2024-04-29 17:30:36',
                'deleted_at' => null,
            ],
            [
                'id' => 18,
                'admin_id' => 175889,
                'disabled_dates' => '2024-11-29',
                'created_at' => '2024-04-29 17:31:03',
                'updated_at' => '2024-04-29 17:31:03',
                'deleted_at' => null,
            ],
            [
                'id' => 19,
                'admin_id' => 175889,
                'disabled_dates' => '2024-12-30',
                'created_at' => '2024-04-29 17:31:19',
                'updated_at' => '2024-04-29 17:31:19',
                'deleted_at' => null,
            ],
            [
                'id' => 20,
                'admin_id' => 175889,
                'disabled_dates' => '2024-12-31',
                'created_at' => '2024-04-29 17:31:30',
                'updated_at' => '2024-04-29 17:31:30',
                'deleted_at' => null,
            ],
            [
                'id' => 21,
                'admin_id' => 175889,
                'disabled_dates' => '2025-01-01',
                'created_at' => '2024-04-29 17:31:43',
                'updated_at' => '2024-04-29 17:31:43',
                'deleted_at' => null,
            ],
            [
                'id' => 22,
                'admin_id' => 175889,
                'disabled_dates' => '2024-12-23',
                'created_at' => '2024-04-29 17:32:11',
                'updated_at' => '2024-04-29 17:32:11',
                'deleted_at' => null,
            ],
            [
                'id' => 23,
                'admin_id' => 175889,
                'disabled_dates' => '2024-12-24',
                'created_at' => '2024-04-29 17:32:23',
                'updated_at' => '2024-04-29 17:32:23',
                'deleted_at' => null,
            ],
            [
                'id' => 24,
                'admin_id' => 175889,
                'disabled_dates' => '2024-12-25',
                'created_at' => '2024-04-29 17:32:35',
                'updated_at' => '2024-04-29 17:32:35',
                'deleted_at' => null,
            ],
            [
                'id' => 25,
                'admin_id' => 175889,
                'disabled_dates' => '2024-07-05',
                'created_at' => '2024-07-01 12:32:57',
                'updated_at' => '2024-07-01 12:32:57',
                'deleted_at' => null,
            ],
            [
                'id' => 26,
                'admin_id' => 175889,
                'disabled_dates' => '2024-07-04',
                'created_at' => '2024-07-03 18:11:08',
                'updated_at' => '2024-07-03 18:11:08',
                'deleted_at' => null,
            ],
            [
                'id' => 27,
                'admin_id' => 175889,
                'disabled_dates' => '2024-09-02',
                'created_at' => '2024-08-27 19:25:34',
                'updated_at' => '2024-08-27 19:25:34',
                'deleted_at' => null,
            ],
            [
                'id' => 29,
                'admin_id' => 175889,
                'disabled_dates' => '2025-07-04',
                'created_at' => '2025-04-17 12:28:33',
                'updated_at' => '2025-04-17 12:28:33',
                'deleted_at' => null,
            ],
            [
                'id' => 30,
                'admin_id' => 175889,
                'disabled_dates' => '2025-09-01',
                'created_at' => '2025-04-17 12:29:08',
                'updated_at' => '2025-04-17 12:29:08',
                'deleted_at' => null,
            ],
            [
                'id' => 31,
                'admin_id' => 175889,
                'disabled_dates' => '2025-05-26',
                'created_at' => '2025-05-19 12:27:04',
                'updated_at' => '2025-05-19 12:27:04',
                'deleted_at' => null,
            ],
            [
                'id' => 32,
                'admin_id' => 175889,
                'disabled_dates' => '2025-12-24',
                'created_at' => '2025-12-16 22:23:20',
                'updated_at' => '2025-12-16 22:23:20',
                'deleted_at' => null,
            ],
            [
                'id' => 33,
                'admin_id' => 175889,
                'disabled_dates' => '2025-12-25',
                'created_at' => '2025-12-16 22:23:25',
                'updated_at' => '2025-12-16 22:23:25',
                'deleted_at' => null,
            ],
            [
                'id' => 34,
                'admin_id' => 175889,
                'disabled_dates' => '2025-12-26',
                'created_at' => '2025-12-16 22:23:30',
                'updated_at' => '2025-12-16 22:23:30',
                'deleted_at' => null,
            ],
            [
                'id' => 35,
                'admin_id' => 175889,
                'disabled_dates' => '2025-12-31',
                'created_at' => '2025-12-16 22:23:35',
                'updated_at' => '2025-12-16 22:23:35',
                'deleted_at' => null,
            ],
            [
                'id' => 36,
                'admin_id' => 175889,
                'disabled_dates' => '2026-01-01',
                'created_at' => '2025-12-16 22:23:39',
                'updated_at' => '2025-12-16 22:23:39',
                'deleted_at' => null,
            ],
            [
                'id' => 37,
                'admin_id' => 175889,
                'disabled_dates' => '2026-01-02',
                'created_at' => '2025-12-16 22:23:42',
                'updated_at' => '2025-12-16 22:23:42',
                'deleted_at' => null,
            ],
        ];

        DB::table('disable_dates')->insert($dataTables);
    }
}
