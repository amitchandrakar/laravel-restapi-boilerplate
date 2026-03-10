<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupsSeeder extends Seeder
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
                'name' => 'Administrator',
            ],
            [
                'id' => 2,
                'name' => 'District Manager',
            ],
            [
                'id' => 3,
                'name' => 'Menu Administrator',
            ],
            [
                'id' => 4,
                'name' => 'General Manager',
            ],
            [
                'id' => 5,
                'name' => 'Customer',
            ],
            [
                'id' => 6,
                'name' => 'Order Processor',
            ],
            [
                'id' => 7,
                'name' => 'Website Manager',
            ],
            [
                'id' => 8,
                'name' => 'Catering Sales Manager',
            ],
            [
                'id' => 9,
                'name' => 'Accounting Dept',
            ],
            [
                'id' => 10,
                'name' => 'Research Company User',
            ],
            [
                'id' => 11,
                'name' => 'Cafe Assistant',
            ],
            [
                'id' => 12,
                'name' => 'eT Manager',
            ],
            [
                'id' => 13,
                'name' => 'Email Marketer',
            ],
            [
                'id' => 14,
                'name' => 'Regional Manager',
            ],
            [
                'id' => 15,
                'name' => 'Assistant Catering Sales Manager',
            ],
            [
                'id' => 16,
                'name' => 'Studio Brand Collective',
            ],
        ];

        DB::table('groups')->insert($dataTables);
    }
}
