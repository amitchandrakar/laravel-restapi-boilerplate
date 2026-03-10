<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomermenusSeeder extends Seeder
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
                'menu_name' => 'Alonti Catering Menu',
                'mini' => 10,
                'percnt' => 0.1,
                'flag' => 1,
            ],
            [
                'id' => 13,
                'menu_name' => 'Alonti Catering Menu - New York City',
                'mini' => 5,
                'percnt' => 0.07,
                'flag' => 0,
            ],
            [
                'id' => 14,
                'menu_name' => 'Alonti Catering Menu For Waste Management',
                'mini' => 0,
                'percnt' => 0,
                'flag' => 0,
            ],
            [
                'id' => 15,
                'menu_name' => 'Alonti Catering Menu For Kerr McGee',
                'mini' => 0,
                'percnt' => 0,
                'flag' => 0,
            ],
            [
                'id' => 16,
                'menu_name' => 'Holiday Menu 2005',
                'mini' => 5,
                'percnt' => 0.07,
                'flag' => 1,
            ],
            [
                'id' => 18,
                'menu_name' => 'Holiday Menu 2006',
                'mini' => 0,
                'percnt' => 0,
                'flag' => 1,
            ],
            [
                'id' => 20,
                'menu_name' => 'Holiday Menu 2007',
                'mini' => 0,
                'percnt' => 0,
                'flag' => 1,
            ],
            [
                'id' => 22,
                'menu_name' => 'Holiday Menu 2007',
                'mini' => 0,
                'percnt' => 0,
                'flag' => 1,
            ],
            [
                'id' => 24,
                'menu_name' => 'Holiday Menu 2008',
                'mini' => 5,
                'percnt' => 0.07,
                'flag' => 1,
            ],
            [
                'id' => 25,
                'menu_name' => 'eT Catering Menu',
                'mini' => 7,
                'percnt' => 0.08,
                'flag' => 1,
            ],
        ];

        DB::table('customermenus')->insert($dataTables);
    }
}
