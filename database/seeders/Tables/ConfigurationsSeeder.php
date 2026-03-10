<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigurationsSeeder extends Seeder
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
                'column_key' => 'state_id',
                'column_value' => 6,
                'field_key' => 'delivery_fee',
                'field_value' => 6,
                'changed_by' => 120277,
                'comments' => 'Warm cookie delivery fee for active states',
            ],
            [
                'id' => 2,
                'column_key' => 'state_id',
                'column_value' => 17,
                'field_key' => 'delivery_fee',
                'field_value' => 6,
                'changed_by' => 120277,
                'comments' => 'Warm cookie delivery fee for active states',
            ],
            [
                'id' => 3,
                'column_key' => 'state_id',
                'column_value' => 51,
                'field_key' => 'delivery_fee',
                'field_value' => 5,
                'changed_by' => 120277,
                'comments' => 'Warm cookie delivery fee for active states',
            ],
            [
                'id' => 4,
                'column_key' => 'reward_type',
                'column_value' => 'percentage',
                'field_key' => 'reward_value',
                'field_value' => '7.5',
                'changed_by' => 175889,
                'comments' => 'Alonti rewards - amazon gift card coupon value for each orders. It will be percentage or dollar',
            ],
            [
                'id' => 5,
                'column_key' => 'referral-reward-value',
                'column_value' => 50,
                'field_key' => 'referral-range-value',
                'field_value' => 200,
                'changed_by' => 175889,
                'comments' => 'Alonti referral - amazon gift card coupon value for customer first order with the given range. It will be a dollar value',
            ],
        ];

        DB::table('configurations')->insert($dataTables);
    }
}
