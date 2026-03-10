<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActiveMenusSeeder extends Seeder
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
                'name' => 'Breakfast',
                'status' => 'active',
                'display_order' => 1,
                'image' => '',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 2,
                'name' => 'Sandwiches, Pressatas & Wraps',
                'status' => 'active',
                'display_order' => 2,
                'image' => '',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 3,
                'name' => 'Package Deals',
                'status' => 'active',
                'display_order' => 3,
                'image' => '',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 4,
                'name' => 'Salads',
                'status' => 'active',
                'display_order' => 4,
                'image' => '',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 5,
                'name' => 'Box Lunches	',
                'status' => 'active',
                'display_order' => 5,
                'image' => '',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 6,
                'name' => 'Hot Plates & Sides',
                'status' => 'active',
                'display_order' => 6,
                'image' => '',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 7,
                'name' => 'Hors d\' Oeuvres',
                'status' => 'active',
                'display_order' => 7,
                'image' => '',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 8,
                'name' => 'Desserts',
                'status' => 'active',
                'display_order' => 8,
                'image' => '',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
            [
                'id' => 9,
                'name' => 'Hot & Cold Beverages',
                'status' => 'active',
                'display_order' => 9,
                'image' => '',
                'created' => '2021-01-11 04:34:57',
                'modified' => '2021-01-11 04:34:57',
            ],
        ];

        DB::table('active_menus')->insert($dataTables);
    }
}
