<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaytraceSettingsSeeder extends Seeder
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
                'environment' => 'sandbox',
                'sandbox_username' => 'demo123',
                'sandbox_password' => 'demo123',
                'live_username' => 'demo123',
                'live_password' => 'demo123',
                'alarm' => 'no',
                'alarm_emails' => '',
                'days_after' => '',
                'updated_by' => null,
                'updated_at' => null,
                'created_at' => null,
            ],
        ];

        DB::table('paytrace_settings')->insert($dataTables);
    }
}
