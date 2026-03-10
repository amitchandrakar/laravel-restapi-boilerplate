<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminAccessTracksSeeder extends Seeder
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
                'contoller' => null,
                'action' => 'admin-users/dashboard',
                'admin_id' => 120277,
                'admin_name' => 'Softway solutions',
                'created_at' => '2026-03-10 10:31:18',
                'updated_at' => '2026-03-10 10:31:18',
            ],
            [
                'id' => 2,
                'contoller' => null,
                'action' => 'alonti-users/customer-search',
                'admin_id' => 120277,
                'admin_name' => 'Softway solutions',
                'created_at' => '2026-03-10 10:31:23',
                'updated_at' => '2026-03-10 10:31:23',
            ],
            [
                'id' => 3,
                'contoller' => null,
                'action' => 'admin-users/dashboard',
                'admin_id' => 120277,
                'admin_name' => 'Softway solutions',
                'created_at' => '2026-03-10 10:31:26',
                'updated_at' => '2026-03-10 10:31:26',
            ],
        ];

        DB::table('admin_access_tracks')->insert($dataTables);
    }
}
