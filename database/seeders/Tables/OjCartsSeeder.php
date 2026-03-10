<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OjCartsSeeder extends Seeder
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
                'id' => 510945,
                'order_id' => 2150210,
                'session_id' => 'xQn14Qu52yxSxqpq3zIoL484kaMGVnqUagDuMVLg',
                'user_id' => null,
                'cafe_id' => 67,
                'group_order_id' => null,
                'promotion_type_id' => null,
                'discount' => 0,
                'discount_type' => null,
                'coupon_id' => null,
                'subtotal' => 992.95,
                'taxable' => '992.95',
                'nontaxable' => 0,
                'delivery_fee' => '99.30',
                'sales_tax' => '114.69',
                'total' => '1206.94',
                'gratuity_percentage' => null,
                'gratuity' => null,
                'zipcode' => 90002,
                'state_id' => 6,
                'group_order_notes' => null,
                'type_of_checkout' => 1,
                'payment_id' => null,
                'company_payment_access_number' => null,
                'cim_payment_profile_id' => null,
                'cim_profile_id' => null,
                'payment_profile_id' => null,
                'personalized_message' => null,
                'order_name' => null,
                'order_status' => null,
                'status' => 0,
                'gift_card_rewards' => 0,
                'created_at' => '2026-01-20 05:35:29',
                'updated_at' => '2026-01-20 06:01:06',
                'deleted_at' => null,
                'amazon_reward_applied' => 0,
            ],
            [
                'id' => 514383,
                'order_id' => 2150220,
                'session_id' => null,
                'user_id' => 136883,
                'cafe_id' => 67,
                'group_order_id' => null,
                'promotion_type_id' => null,
                'discount' => 0,
                'discount_type' => null,
                'coupon_id' => null,
                'subtotal' => 13.9,
                'taxable' => '13.90',
                'nontaxable' => 0,
                'delivery_fee' => 10,
                'sales_tax' => '1.97',
                'total' => '25.87',
                'gratuity_percentage' => 0,
                'gratuity' => null,
                'zipcode' => 77019,
                'state_id' => 51,
                'group_order_notes' => null,
                'type_of_checkout' => 1,
                'payment_id' => 1,
                'company_payment_access_number' => null,
                'cim_payment_profile_id' => null,
                'cim_profile_id' => null,
                'payment_profile_id' => null,
                'personalized_message' => '',
                'order_name' => null,
                'order_status' => null,
                'status' => 0,
                'gift_card_rewards' => 1,
                'created_at' => '2026-02-13 03:15:01',
                'updated_at' => '2026-02-13 03:16:43',
                'deleted_at' => null,
                'amazon_reward_applied' => 0,
            ],
        ];

        DB::table('oj_carts')->insert($dataTables);
    }
}
