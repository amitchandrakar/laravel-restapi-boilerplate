<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentsSeeder extends Seeder
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
                'terms' => 'Cash (C.O.D]',
                'flag' => 1,
                'sort' => 4,
                'default_payment' => 1,
                'visibility' => 0,
                'acumatica_term_id' => 'COD',
                'acumatica_payment_method_id' => 'COD',
            ],
            [
                'id' => 2,
                'terms' => 'House Account - Standard Terms – Net 30 Days',
                'flag' => 1,
                'sort' => 3,
                'default_payment' => 0,
                'visibility' => 0,
                'acumatica_term_id' => 30,
                'acumatica_payment_method_id' => 'CHECK',
            ],
            [
                'id' => 3,
                'terms' => 'Temporary',
                'flag' => 1,
                'sort' => 1,
                'default_payment' => 0,
                'visibility' => 0,
                'acumatica_term_id' => null,
                'acumatica_payment_method_id' => null,
            ],
            [
                'id' => 4,
                'terms' => 'Credit Card - Payment On Delivery',
                'flag' => 1,
                'sort' => 2,
                'default_payment' => 1,
                'visibility' => 0,
                'acumatica_term_id' => 'CC',
                'acumatica_payment_method_id' => 'CC',
            ],
            [
                'id' => 7,
                'terms' => 'Purchase Order Only',
                'flag' => 1,
                'sort' => 5,
                'default_payment' => 0,
                'visibility' => 0,
                'acumatica_term_id' => 30,
                'acumatica_payment_method_id' => 'CHECK',
            ],
            [
                'id' => 8,
                'terms' => 'Go Fund Me',
                'flag' => 1,
                'sort' => 6,
                'default_payment' => 0,
                'visibility' => 0,
                'acumatica_term_id' => null,
                'acumatica_payment_method_id' => null,
            ],
        ];

        DB::table('payments')->insert($dataTables);
    }
}
