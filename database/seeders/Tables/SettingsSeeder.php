<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
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
                'google_status' => 'active',
                'google_app_id' => env('GOOGLE_CLIENT_ID', ''),
                'google_secret' => env('GOOGLE_CLIENT_SECRET', ''),
                'facebook_status' => 'active',
                'facebook_app_id' => env('FACEBOOK_APP_ID', ''),
                'facebook_secret' => env('FACEBOOK_APP_SECRET', ''),
                'linkedin_status' => 'active',
                'linkedin_app_id' => env('LINKEDIN_CLIENT_ID', ''),
                'linkedin_secret' => env('LINKEDIN_CLIENT_SECRET', ''),
                'twitter_status' => 'inactive',
                'twitter_app_id' => env('TWITTER_CLIENT_ID', ''),
                'twitter_secret' => env('TWITTER_CLIENT_SECRET', ''),
                'paytrace_password' => env('PAYTRACE_PASSWORD', ''),
                'amazon_reward_min_spend' => 0,
            ],
        ];

        DB::table('settings')->insert($dataTables);
    }
}
