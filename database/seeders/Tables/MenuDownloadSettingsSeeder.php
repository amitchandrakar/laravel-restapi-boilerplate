<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuDownloadSettingsSeeder extends Seeder
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
                'section_1_title' => 'Alonti Holiday Menu',
                'section_1_text_1' => 'Celebrate the season with Alonti’s Holiday Menu, featuring classic favorites, festive sides, and indulgent desserts designed to wow your team. Whether it’s a morning feast, holiday buffet, or a spread of party bites, we make every gathering feel warm, delicious, and effortless.
',
                'section_1_text_2' => '',
                'section_1_status' => 'inactive',
                'section_1_texas_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FduPjIkG9fZmVxQwsU7pCoMy061lSq5EtgRvLrzaeO2WXFHKJiAAlonti+2025+Holiday+Menu+TX+%26+GA+%287%29.pdf',
                'section_1_texas_menu_key' => 'pdf/duPjIkG9fZmVxQwsU7pCoMy061lSq5EtgRvLrzaeO2WXFHKJiAAlonti 2025 Holiday Menu TX & GA (7].pdf',
                'section_1_georgia_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FPfIntJjBh23CeKlb01NHvWO7XkodVsMAYT6FpzxwDUc5Gya98gAlonti+2025+Holiday+Menu+TX+%26+GA+%287%29.pdf',
                'section_1_georgia_menu_key' => 'pdf/PfIntJjBh23CeKlb01NHvWO7XkodVsMAYT6FpzxwDUc5Gya98gAlonti 2025 Holiday Menu TX & GA (7].pdf',
                'section_1_illinois_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2F4gofliMP8tDseVcxZSYmwO3XGk5bvAW62QqEUrB0zKpRLdHNhCAlonti+2025+Holiday+Menu+IL+%282%29.pdf',
                'section_1_illinois_menu_key' => 'pdf/4gofliMP8tDseVcxZSYmwO3XGk5bvAW62QqEUrB0zKpRLdHNhCAlonti 2025 Holiday Menu IL (2].pdf',
                'section_1_california_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FZmB43VIgRMYPFdkfLeySW2QuACO0xiJ58cjq9nzrosap1XvEUhAlonti+2025+Holiday+Menu+CA+%282%29.pdf',
                'section_1_california_menu_key' => 'pdf/ZmB43VIgRMYPFdkfLeySW2QuACO0xiJ58cjq9nzrosap1XvEUhAlonti 2025 Holiday Menu CA (2].pdf',
                'section_2_title' => 'The Catering Menu',
                'section_2_text_1' => 'Our year round catering menu, perfect for any occasion!',
                'section_2_text_2' => '',
                'section_2_status' => 'active',
                'section_2_texas_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FQjONzY8XAbL2g605sKHxo9tDdueTUhVcSRilCkF3nIvBwqGfMWAlonti+Menu+TX-24+%285%29.pdf',
                'section_2_texas_menu_key' => 'pdf/QjONzY8XAbL2g605sKHxo9tDdueTUhVcSRilCkF3nIvBwqGfMWAlonti Menu TX-24 (5].pdf',
                'section_2_georgia_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FbAfjclCDX9VKEOuyUn7Re5YiNrFG6moTQ0qxgdp231IHS8aWLzAlonti+Menu+TX-24+%285%29.pdf',
                'section_2_georgia_menu_key' => 'pdf/bAfjclCDX9VKEOuyUn7Re5YiNrFG6moTQ0qxgdp231IHS8aWLzAlonti Menu TX-24 (5].pdf',
                'section_2_illinois_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FNSxRB8d4lqHvKMT5XpzsyoCLWm7jOGurJA3fYVZtcE2bh6I1PgAlonti+Menu+IL-24.pdf',
                'section_2_illinois_menu_key' => 'pdf/NSxRB8d4lqHvKMT5XpzsyoCLWm7jOGurJA3fYVZtcE2bh6I1PgAlonti Menu IL-24.pdf',
                'section_2_california_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FMBRq9ZVJd3brwlumXS5FHsLN68KDoI120pfizWAChey4PngOcUAlonti+Menu+CA-24.pdf',
                'section_2_california_menu_key' => 'pdf/MBRq9ZVJd3brwlumXS5FHsLN68KDoI120pfizWAChey4PngOcUAlonti Menu CA-24.pdf',
                'section_3_title' => 'INDIVIDUAL. CUSTOM. DELICIOUS. BOXED MEALS.',
                'section_3_text_1' => 'Fuel their day with tasty individual breakfast and lunch boxes from Alonti.',
                'section_3_text_2' => '',
                'section_3_status' => 'active',
                'section_3_texas_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FlQOJWybPfpcNzuK51MBtCRk0EjU6vnoLHG7TsVrdh28I3gDiZYBox+Lunch+Menu+TX+_+GA.pdf',
                'section_3_texas_menu_key' => 'pdf/lQOJWybPfpcNzuK51MBtCRk0EjU6vnoLHG7TsVrdh28I3gDiZYBox Lunch Menu TX _ GA.pdf',
                'section_3_georgia_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FASvzycfdpGjrh9BONoDk6JtE4YMQuWeT8HgbVnmF5XxlPUCLIRBox+Lunch+Menu+TX+_+GA.pdf',
                'section_3_georgia_menu_key' => 'pdf/ASvzycfdpGjrh9BONoDk6JtE4YMQuWeT8HgbVnmF5XxlPUCLIRBox Lunch Menu TX _ GA.pdf',
                'section_3_illinois_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FmuRkN7GioU2D0gczT5xOPBsHtbY4peMASQFh9WjdlrXEIvCanZBox+Lunch+Menu+IL.pdf',
                'section_3_illinois_menu_key' => 'pdf/muRkN7GioU2D0gczT5xOPBsHtbY4peMASQFh9WjdlrXEIvCanZBox Lunch Menu IL.pdf',
                'section_3_california_menu' => 'https://alonti-live.s3.amazonaws.com/pdf%2FIlYJocmGyHSt7DWBjkFUOR6euVPdhzA9aEgwNs032XTrfZbx4MBox+Lunch+Menu+CA.pdf',
                'section_3_california_menu_key' => 'pdf/IlYJocmGyHSt7DWBjkFUOR6euVPdhzA9aEgwNs032XTrfZbx4MBox Lunch Menu CA.pdf',
            ],
        ];

        DB::table('menu_download_settings')->insert($dataTables);
    }
}
