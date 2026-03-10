<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AclPhinxlogSeeder extends Seeder
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
                'version' => 20141229162641,
                'migration_name' => 'DbAcl',
                'start_time' => '2016-07-30 09:24:14',
                'end_time' => '2016-07-30 09:24:14',
            ],
        ];

        DB::table('acl_phinxlog')->insert($dataTables);
    }
}
