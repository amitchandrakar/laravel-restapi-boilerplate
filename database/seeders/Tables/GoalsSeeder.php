<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoalsSeeder extends Seeder
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
                'goal_id' => 200711,
                'year' => 2007,
                'quarter' => 1,
                'mk_id' => 1,
                'goal' => 0,
            ],
            [
                'goal_id' => 200712,
                'year' => 2007,
                'quarter' => 2,
                'mk_id' => 1,
                'goal' => 0,
            ],
            [
                'goal_id' => 200713,
                'year' => 2007,
                'quarter' => 3,
                'mk_id' => 1,
                'goal' => 0,
            ],
            [
                'goal_id' => 200714,
                'year' => 2007,
                'quarter' => 4,
                'mk_id' => 1,
                'goal' => 0,
            ],
            [
                'goal_id' => 200721,
                'year' => 2007,
                'quarter' => 1,
                'mk_id' => 2,
                'goal' => '4756.5100',
            ],
            [
                'goal_id' => 200722,
                'year' => 2007,
                'quarter' => 2,
                'mk_id' => 2,
                'goal' => 0,
            ],
            [
                'goal_id' => 200723,
                'year' => 2007,
                'quarter' => 3,
                'mk_id' => 2,
                'goal' => 0,
            ],
            [
                'goal_id' => 200724,
                'year' => 2007,
                'quarter' => 4,
                'mk_id' => 2,
                'goal' => 0,
            ],
            [
                'goal_id' => 200741,
                'year' => 2007,
                'quarter' => 1,
                'mk_id' => 4,
                'goal' => 0,
            ],
            [
                'goal_id' => 200742,
                'year' => 2007,
                'quarter' => 2,
                'mk_id' => 4,
                'goal' => 0,
            ],
            [
                'goal_id' => 200743,
                'year' => 2007,
                'quarter' => 3,
                'mk_id' => 4,
                'goal' => 0,
            ],
            [
                'goal_id' => 200744,
                'year' => 2007,
                'quarter' => 4,
                'mk_id' => 4,
                'goal' => 0,
            ],
            [
                'goal_id' => 200791,
                'year' => 2007,
                'quarter' => 1,
                'mk_id' => 9,
                'goal' => 0,
            ],
            [
                'goal_id' => 200792,
                'year' => 2007,
                'quarter' => 2,
                'mk_id' => 9,
                'goal' => 0,
            ],
            [
                'goal_id' => 200793,
                'year' => 2007,
                'quarter' => 3,
                'mk_id' => 9,
                'goal' => 0,
            ],
            [
                'goal_id' => 200794,
                'year' => 2007,
                'quarter' => 4,
                'mk_id' => 9,
                'goal' => 0,
            ],
        ];

        DB::table('goals')->insert($dataTables);
    }
}
