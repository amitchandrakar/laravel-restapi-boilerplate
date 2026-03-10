<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoglistSeeder extends Seeder
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
                'ID' => 10,
                'Cog ID' => 3501,
                'Cog Name' => 'Other',
            ],
            [
                'ID' => 11,
                'Cog ID' => '3401D',
                'Cog Name' => 'Vendor Rebates',
            ],
            [
                'ID' => 6,
                'Cog ID' => '3401A',
                'Cog Name' => 'Food',
            ],
            [
                'ID' => 7,
                'Cog ID' => '3401B',
                'Cog Name' => 'Janitorial',
            ],
            [
                'ID' => 8,
                'Cog ID' => '3401C',
                'Cog Name' => 'Paper',
            ],
        ];

        DB::table('coglist')->insert($dataTables);
    }
}
