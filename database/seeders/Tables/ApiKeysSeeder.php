<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApiKeysSeeder extends Seeder
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
                'user_id' => null,
                'key' => 'a8449ca1c2eee258c05b92136e32c2b5219c286d',
                'level' => 10,
                'ignore_limits' => 1,
                'created_at' => '2016-12-27 11:15:01',
                'updated_at' => '2016-12-27 11:15:01',
                'deleted_at' => null,
            ],
        ];

        DB::table('api_keys')->insert($dataTables);
    }
}
