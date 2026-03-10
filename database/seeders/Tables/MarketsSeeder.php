<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketsSeeder extends Seeder
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
                'name' => 'Dallas-Fort Worth',
                'customermenu_id' => 1,
                'gl_code_id' => 2,
                'timezone_difference' => -6,
                'allow_weekend_orders' => 1,
                'allow_night_orders' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Houston Closed ',
                'customermenu_id' => 1,
                'gl_code_id' => 1,
                'timezone_difference' => -6,
                'allow_weekend_orders' => 1,
                'allow_night_orders' => 1,
            ],
            [
                'id' => 4,
                'name' => 'Chicago',
                'customermenu_id' => 1,
                'gl_code_id' => 3,
                'timezone_difference' => -6,
                'allow_weekend_orders' => 0,
                'allow_night_orders' => 0,
            ],
            [
                'id' => 9,
                'name' => 'Houston',
                'customermenu_id' => 1,
                'gl_code_id' => 1,
                'timezone_difference' => -6,
                'allow_weekend_orders' => 1,
                'allow_night_orders' => 1,
            ],
            [
                'id' => 13,
                'name' => 'Central Texas',
                'customermenu_id' => 1,
                'gl_code_id' => 2,
                'timezone_difference' => -6,
                'allow_weekend_orders' => 0,
                'allow_night_orders' => 0,
            ],
            [
                'id' => 16,
                'name' => 'LA/Orange County',
                'customermenu_id' => 1,
                'gl_code_id' => 15,
                'timezone_difference' => -8,
                'allow_weekend_orders' => 0,
                'allow_night_orders' => 0,
            ],
            [
                'id' => 17,
                'name' => 'Atlanta',
                'customermenu_id' => 1,
                'gl_code_id' => 17,
                'timezone_difference' => -5,
                'allow_weekend_orders' => 1,
                'allow_night_orders' => 1,
            ],
        ];

        DB::table('markets')->insert($dataTables);
    }
}
