<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlontiLibrariesSeeder extends Seeder
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
                'id' => 3,
                'cat' => 'RECIPES',
                'sort' => 1,
                'hidden' => 'YES',
            ],
            [
                'id' => 6,
                'cat' => 'WEBSITE ',
                'sort' => 5,
                'hidden' => 'YES',
            ],
            [
                'id' => 7,
                'cat' => 'HR, PAYROLL & INSURANCE',
                'sort' => 3,
                'hidden' => 'YES',
            ],
            [
                'id' => 8,
                'cat' => 'MICROS POS ',
                'sort' => 6,
                'hidden' => 'YES',
            ],
            [
                'id' => 9,
                'cat' => 'ACCOUNTING FORMS',
                'sort' => 4,
                'hidden' => 'YES',
            ],
            [
                'id' => 13,
                'cat' => 'COMPANY DIRECTORY',
                'sort' => 10,
                'hidden' => '',
            ],
            [
                'id' => 14,
                'cat' => 'Test',
                'sort' => 6,
                'hidden' => 'YES',
            ],
            [
                'id' => 15,
                'cat' => 'CHARTS',
                'sort' => 2,
                'hidden' => 'YES',
            ],
            [
                'id' => 16,
                'cat' => 'Test Category',
                'sort' => 22,
                'hidden' => 'YES',
            ],
            [
                'id' => 17,
                'cat' => '**Test Category**',
                'sort' => 7,
                'hidden' => 'YES',
            ],
            [
                'id' => 18,
                'cat' => 'Recipes ',
                'sort' => 15,
                'hidden' => 'YES',
            ],
            [
                'id' => 19,
                'cat' => 'CHARTS',
                'sort' => 20,
                'hidden' => 'YES',
            ],
            [
                'id' => 20,
                'cat' => 'HR, PAYROLL & INSURANCE',
                'sort' => 30,
                'hidden' => 'YES',
            ],
            [
                'id' => 21,
                'cat' => 'ACCOUNTING FORMS ',
                'sort' => 40,
                'hidden' => '',
            ],
            [
                'id' => 22,
                'cat' => 'WEBSITE',
                'sort' => 50,
                'hidden' => '',
            ],
            [
                'id' => 23,
                'cat' => 'WEBSITE',
                'sort' => 5,
                'hidden' => 'YES',
            ],
            [
                'id' => 24,
                'cat' => 'MICROS POS',
                'sort' => 60,
                'hidden' => '',
            ],
            [
                'id' => 25,
                'cat' => 'TRAINING GUIDES',
                'sort' => 7,
                'hidden' => 'YES',
            ],
            [
                'id' => 26,
                'cat' => 'ADMINISTRATIVE TRAINING GUIDES',
                'sort' => 70,
                'hidden' => '',
            ],
            [
                'id' => 27,
                'cat' => 'RECIPES',
                'sort' => 100,
                'hidden' => '',
            ],
            [
                'id' => 28,
                'cat' => 'Build Sheets',
                'sort' => 8,
                'hidden' => 'YES',
            ],
            [
                'id' => 29,
                'cat' => 'ALL STAR TRACKER',
                'sort' => 0,
                'hidden' => '',
            ],
            [
                'id' => 30,
                'cat' => 'SAFETY MANUAL',
                'sort' => 5,
                'hidden' => '',
            ],
            [
                'id' => 31,
                'cat' => 'HR',
                'sort' => 200,
                'hidden' => '',
            ],
            [
                'id' => 32,
                'cat' => 'DAILY SALES REPORTING',
                'sort' => 300,
                'hidden' => '',
            ],
        ];

        DB::table('alonti_libraries')->insert($dataTables);
    }
}
