<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OjDietariesSeeder extends Seeder
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
                'name' => 'Gluten-free',
                'status' => 1,
                'created_at' => '2019-11-20 13:36:49',
                'updated_at' => '2019-12-23 10:04:29',
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'name' => 'Vegan',
                'status' => 1,
                'created_at' => '2019-11-20 13:36:49',
                'updated_at' => '2019-11-20 13:36:49',
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'name' => 'Vegetarian',
                'status' => 1,
                'created_at' => '2019-11-20 13:36:49',
                'updated_at' => '2019-11-20 13:36:49',
                'deleted_at' => null,
            ],
            [
                'id' => 4,
                'name' => 'Individually Packaged',
                'status' => 1,
                'created_at' => '2020-05-19 15:05:21',
                'updated_at' => '2020-05-26 20:37:21',
                'deleted_at' => null,
            ],
        ];

        DB::table('oj_dietaries')->insert($dataTables);
    }
}
