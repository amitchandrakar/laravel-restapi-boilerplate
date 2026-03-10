<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnticipatedelitestatusSeeder extends Seeder
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
                'OptionID' => 2,
                'OptionText' => 'Gold',
                'Sort' => 20,
                'Isactive' => 1,
            ],
            [
                'OptionID' => 4,
                'OptionText' => 'Non-elite',
                'Sort' => 40,
                'Isactive' => 1,
            ],
            [
                'OptionID' => 5,
                'OptionText' => 'None',
                'Sort' => 50,
                'Isactive' => 1,
            ],
            [
                'OptionID' => 1,
                'OptionText' => 'Platinum',
                'Sort' => 10,
                'Isactive' => 1,
            ],
            [
                'OptionID' => 3,
                'OptionText' => 'Silver',
                'Sort' => 30,
                'Isactive' => 1,
            ],
        ];

        DB::table('anticipatedelitestatus')->insert($dataTables);
    }
}
