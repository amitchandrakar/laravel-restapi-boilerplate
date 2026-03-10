<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActiveMenuCategoriesSeeder extends Seeder
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
                'category_id' => 31,
                'active_menu_id' => 1,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 2,
                'category_id' => 32,
                'active_menu_id' => 1,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 3,
                'category_id' => 33,
                'active_menu_id' => 2,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 4,
                'category_id' => 34,
                'active_menu_id' => 3,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 5,
                'category_id' => 46,
                'active_menu_id' => 3,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 6,
                'category_id' => 45,
                'active_menu_id' => 3,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 7,
                'category_id' => 36,
                'active_menu_id' => 4,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 8,
                'category_id' => 35,
                'active_menu_id' => 5,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 9,
                'category_id' => 37,
                'active_menu_id' => 6,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 10,
                'category_id' => 39,
                'active_menu_id' => 7,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 11,
                'category_id' => 40,
                'active_menu_id' => 8,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 12,
                'category_id' => 41,
                'active_menu_id' => 9,
                'is_deleted' => 'no',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
        ];

        DB::table('active_menu_categories')->insert($dataTables);
    }
}
