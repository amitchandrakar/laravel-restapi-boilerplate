<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndustriesSeeder extends Seeder
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
                'name' => 'Education',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 2,
                'name' => 'Retail',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 3,
                'name' => 'Business Services',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 4,
                'name' => 'Healthcare',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 5,
                'name' => 'Manufacturing',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 6,
                'name' => 'Consulting',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 7,
                'name' => 'Engineering',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 8,
                'name' => 'Utilities',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 9,
                'name' => 'Energy',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 10,
                'name' => 'Construction',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 11,
                'name' => 'Banking',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 12,
                'name' => 'Finance',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 13,
                'name' => 'Insurance',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 14,
                'name' => 'Technology',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 15,
                'name' => 'Transportation',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 16,
                'name' => 'Electronics',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 17,
                'name' => 'Chemicals',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 18,
                'name' => 'Machinery',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 19,
                'name' => 'Telecommunications',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 20,
                'name' => 'Communications',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 21,
                'name' => 'Entertainment',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 22,
                'name' => 'Government',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 23,
                'name' => 'Agriculture',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 24,
                'name' => 'Hospitality',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 25,
                'name' => 'Biotechnology',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 26,
                'name' => 'Shipping',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 27,
                'name' => 'Apparel',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 28,
                'name' => 'Food & Beverage',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 29,
                'name' => 'Recreation',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 30,
                'name' => 'Media',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 31,
                'name' => 'Non-profit',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
            [
                'id' => 99,
                'name' => 'Other',
                'created' => '2017-03-07 08:03:48',
                'modified' => '2017-03-07 08:03:48',
            ],
        ];

        DB::table('industries')->insert($dataTables);
    }
}
