<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailQueuePhinxlogSeeder extends Seeder
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
                'version' => 20160324054602,
                'migration_name' => 'Initial',
                'start_time' => '2020-01-04 04:04:27',
                'end_time' => '2020-01-04 04:04:27',
                'breakpoint' => 0,
            ],
            [
                'version' => 20160810121455,
                'migration_name' => 'AddAttachmentsToEmailQueue',
                'start_time' => '2020-01-04 04:04:27',
                'end_time' => '2020-01-04 04:04:27',
                'breakpoint' => 0,
            ],
            [
                'version' => 20181120010607,
                'migration_name' => 'AddErrorMessage',
                'start_time' => '2020-01-04 04:04:27',
                'end_time' => '2020-01-04 04:04:27',
                'breakpoint' => 0,
            ],
            [
                'version' => 20190610024410,
                'migration_name' => 'AlterTemplateVarsToEmailQueue',
                'start_time' => '2020-01-04 04:04:27',
                'end_time' => '2020-01-04 04:04:27',
                'breakpoint' => 0,
            ],
            [
                'version' => 20190814000000,
                'migration_name' => 'AlterTemplateToEmailQueue',
                'start_time' => '2020-01-04 04:04:27',
                'end_time' => '2020-01-04 04:04:27',
                'breakpoint' => 0,
            ],
        ];

        DB::table('email_queue_phinxlog')->insert($dataTables);
    }
}
