<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WebsiteBannerSettingsSeeder extends Seeder
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
                'title' => 'Screaming Hot Deals for 2026!!!',
                'description' => '<p>Six of our best-selling menu items, crafted with premium ingredients, now available at special reduced pricing while supplies last!</p>
',
                'link_text' => 'The Deals',
                'link_url' => 'https://www.alonti.com/screaming-hot-deals',
                'banner_image' => 'banner_image/dgso4IVMvJH6eYTApEhxluz5QKFLOGWRaSD2ncj7NX9yiC0mZBAlonti-Specials-V2-2.jpg',
                'image_url' => 'https://alonti-live.s3.amazonaws.com/banner_image%2Fdgso4IVMvJH6eYTApEhxluz5QKFLOGWRaSD2ncj7NX9yiC0mZBAlonti-Specials-V2-2.jpg',
                'updated_by' => 175889,
                'updated_at' => '2026-01-05 16:25:53',
            ],
        ];

        DB::table('website_banner_settings')->insert($dataTables);
    }
}
