<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OjCartOptionsSeeder extends Seeder
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
                'id' => 4321017,
                'cart_item_id' => 2131433,
                'product_option_id' => 382,
                'product_selection_id' => 23,
                'name' => 'Fresh fruit cup side',
                'unit_price' => '3.50',
                'quantity' => 2,
                'total' => 7,
                'state_price_id' => 21006,
                'is_free' => 0,
                'created_at' => '2026-01-20 05:36:24',
                'updated_at' => '2026-01-20 05:51:38',
                'deleted_at' => null,
            ],
            [
                'id' => 4321019,
                'cart_item_id' => 2131434,
                'product_option_id' => 456,
                'product_selection_id' => 535,
                'name' => 'Traditional sandwiches',
                'unit_price' => 0,
                'quantity' => '4.5000',
                'total' => 0,
                'state_price_id' => 23229,
                'is_free' => 0,
                'created_at' => '2026-01-20 05:47:36',
                'updated_at' => '2026-01-20 05:53:15',
                'deleted_at' => null,
            ],
            [
                'id' => 4321020,
                'cart_item_id' => 2131434,
                'product_option_id' => 457,
                'product_selection_id' => 91,
                'name' => 'Tomato basil pasta salad bowl',
                'unit_price' => 0,
                'quantity' => '4.5000',
                'total' => 0,
                'state_price_id' => 17038,
                'is_free' => 0,
                'created_at' => '2026-01-20 05:47:36',
                'updated_at' => '2026-01-20 05:53:15',
                'deleted_at' => null,
            ],
            [
                'id' => 4321021,
                'cart_item_id' => 2131434,
                'product_option_id' => 457,
                'product_selection_id' => 50,
                'name' => 'Mixed green salad bowl',
                'unit_price' => 0,
                'quantity' => '4.5000',
                'total' => 0,
                'state_price_id' => 19678,
                'is_free' => 0,
                'created_at' => '2026-01-20 05:47:36',
                'updated_at' => '2026-01-20 05:53:15',
                'deleted_at' => null,
            ],
            [
                'id' => 4321022,
                'cart_item_id' => 2131434,
                'product_option_id' => 458,
                'product_selection_id' => 188,
                'name' => 'Premium sweets selection',
                'unit_price' => 0,
                'quantity' => '4.5000',
                'total' => 0,
                'state_price_id' => 17598,
                'is_free' => 0,
                'created_at' => '2026-01-20 05:47:36',
                'updated_at' => '2026-01-20 05:53:15',
                'deleted_at' => null,
            ],
            [
                'id' => 4342793,
                'cart_item_id' => 2143166,
                'product_option_id' => 13,
                'product_selection_id' => 246,
                'name' => 'Bacon ciabatta',
                'unit_price' => 0,
                'quantity' => 1,
                'total' => 0,
                'state_price_id' => 17921,
                'is_free' => 0,
                'created_at' => '2026-02-13 03:15:02',
                'updated_at' => '2026-02-13 03:15:54',
                'deleted_at' => null,
            ],
        ];

        DB::table('oj_cart_options')->insert($dataTables);
    }
}
