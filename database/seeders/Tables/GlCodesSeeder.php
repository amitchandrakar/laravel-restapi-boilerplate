<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GlCodesSeeder extends Seeder
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
                'code' => 'CATR',
                'description' => 'Catering Rental',
            ],
            [
                'id' => 2,
                'code' => 'DELV',
                'description' => 'Delivery Fee',
            ],
            [
                'id' => 3,
                'code' => 'FOOD',
                'description' => 'Food',
            ],
            [
                'id' => 4,
                'code' => 'GIFT',
                'description' => 'Gift Certificates',
            ],
            [
                'id' => 5,
                'code' => 'LABR',
                'description' => 'Labor',
            ],
            [
                'id' => 6,
                'code' => 'TIPS',
                'description' => 'Tips',
            ],
            [
                'id' => 7,
                'code' => 'WATE',
                'description' => 'Water',
            ],
        ];

        DB::table('gl_codes')->insert($dataTables);
    }
}
