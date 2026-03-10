<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaravelSuccessJobsSeeder extends Seeder
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
                'job_name' => 'App\\Jobs\\TrackCartOptionJob',
                'payload' => '{"cart_item_id":2038709,"cart_option_id":4143607,"controller":"","action":"\\/invitation\\/cart\\/update","option_info":"{\\"cart_item_id\\":2038709,\\"product_option_id\\":13,\\"product_selection_id\\":247,\\"name\\":\\"Ham ciabatta\\",\\"unit_price\\":\\"0.00\\",\\"quantity\\":3,\\"total\\":0,\\"state_price_id\\":18145,\\"updated_at\\":\\"2025-11-24 08:57:27\\",\\"created_at\\":\\"2025-11-24 08:57:27\\",\\"id\\":4143607}"}',
                'processed_at' => '2025-11-24 08:58:28',
            ],
        ];

        DB::table('laravel_success_jobs')->insert($dataTables);
    }
}
