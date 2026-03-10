<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OffmenuCreditsSeeder extends Seeder
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
                'credit' => 'Meet & Greet',
                'sort' => 1,
                'status' => 1,
            ],
            [
                'id' => 2,
                'credit' => 'First Order 25% discount',
                'sort' => 2,
                'status' => 1,
            ],
            [
                'id' => 3,
                'credit' => 'Mega discount',
                'sort' => 3,
                'status' => 0,
            ],
            [
                'id' => 4,
                'credit' => 'Budget Match',
                'sort' => 4,
                'status' => 1,
            ],
            [
                'id' => 5,
                'credit' => 'Multi Discount > $2k',
                'sort' => 5,
                'status' => 0,
            ],
            [
                'id' => 6,
                'credit' => 'Monday or Friday discount',
                'sort' => 6,
                'status' => 0,
            ],
            [
                'id' => 7,
                'credit' => 'Mistake or Error',
                'sort' => 7,
                'status' => 0,
            ],
            [
                'id' => 8,
                'credit' => 'Other Discount',
                'sort' => 8,
                'status' => 0,
            ],
            [
                'id' => 9,
                'credit' => 'Educator Discount',
                'sort' => 9,
                'status' => 1,
            ],
            [
                'id' => 10,
                'credit' => 'ezCater',
                'sort' => 10,
                'status' => 1,
            ],
        ];

        DB::table('offmenu_credits')->insert($dataTables);
    }
}
