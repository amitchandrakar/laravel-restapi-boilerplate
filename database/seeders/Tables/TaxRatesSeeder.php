<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxRatesSeeder extends Seeder
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
                'zipcode' => 770,
                'rate' => 0.0825,
            ],
            [
                'id' => 3,
                'zipcode' => 606,
                'rate' => 0.115,
            ],
            [
                'id' => 4,
                'zipcode' => 605,
                'rate' => 0.07,
            ],
            [
                'id' => 5,
                'zipcode' => 752,
                'rate' => 0.0825,
            ],
            [
                'id' => 6,
                'zipcode' => 761,
                'rate' => 0.0825,
            ],
            [
                'id' => 7,
                'zipcode' => 750,
                'rate' => 0.0825,
            ],
            [
                'id' => 8,
                'zipcode' => 786,
                'rate' => 0.0825,
            ],
            [
                'id' => 9,
                'zipcode' => 787,
                'rate' => 0.0825,
            ],
            [
                'id' => 10,
                'zipcode' => 781,
                'rate' => 0.0825,
            ],
            [
                'id' => 11,
                'zipcode' => 782,
                'rate' => 0.0825,
            ],
            [
                'id' => 12,
                'zipcode' => 773,
                'rate' => 0.0825,
            ],
            [
                'id' => 13,
                'zipcode' => 601,
                'rate' => 0.12,
            ],
            [
                'id' => 14,
                'zipcode' => 600,
                'rate' => 0.12,
            ],
            [
                'id' => 15,
                'zipcode' => 926,
                'rate' => 0.0775,
            ],
            [
                'id' => 16,
                'zipcode' => 927,
                'rate' => 0.0775,
            ],
            [
                'id' => 17,
                'zipcode' => 774,
                'rate' => 0.0825,
            ],
            [
                'id' => 18,
                'zipcode' => 900,
                'rate' => 0.095,
            ],
            [
                'id' => 19,
                'zipcode' => 901,
                'rate' => 0.0875,
            ],
            [
                'id' => 20,
                'zipcode' => 910,
                'rate' => 0.095,
            ],
            [
                'id' => 21,
                'zipcode' => 911,
                'rate' => 0.095,
            ],
            [
                'id' => 22,
                'zipcode' => 912,
                'rate' => 0.095,
            ],
            [
                'id' => 23,
                'zipcode' => 914,
                'rate' => 0.095,
            ],
            [
                'id' => 24,
                'zipcode' => 915,
                'rate' => 0.095,
            ],
            [
                'id' => 26,
                'zipcode' => 916,
                'rate' => 0.095,
            ],
            [
                'id' => 27,
                'zipcode' => 918,
                'rate' => 0.095,
            ],
            [
                'id' => 33,
                'zipcode' => 906,
                'rate' => 0.0875,
            ],
            [
                'id' => 34,
                'zipcode' => 928,
                'rate' => 0.0875,
            ],
            [
                'id' => 35,
                'zipcode' => 603,
                'rate' => 0.115,
            ],
            [
                'id' => 36,
                'zipcode' => 604,
                'rate' => 0.115,
            ],
            [
                'id' => 37,
                'zipcode' => 608,
                'rate' => 0.115,
            ],
            [
                'id' => 38,
                'zipcode' => 751,
                'rate' => 0.0825,
            ],
            [
                'id' => 39,
                'zipcode' => 753,
                'rate' => 0.0825,
            ],
            [
                'id' => 40,
                'zipcode' => 762,
                'rate' => 0.0825,
            ],
            [
                'id' => 41,
                'zipcode' => 772,
                'rate' => 0.0825,
            ],
            [
                'id' => 42,
                'zipcode' => 775,
                'rate' => 0.0825,
            ],
            [
                'id' => 43,
                'zipcode' => 780,
                'rate' => 0.0825,
            ],
            [
                'id' => 44,
                'zipcode' => 902,
                'rate' => 0.095,
            ],
            [
                'id' => 45,
                'zipcode' => 907,
                'rate' => 0.0875,
            ],
            [
                'id' => 46,
                'zipcode' => 913,
                'rate' => 0.095,
            ],
            [
                'id' => 47,
                'zipcode' => 760,
                'rate' => 0.0825,
            ],
            [
                'id' => 48,
                'zipcode' => 905,
                'rate' => 0.0875,
            ],
            [
                'id' => 49,
                'zipcode' => 908,
                'rate' => 0.0875,
            ],
            [
                'id' => 50,
                'zipcode' => 904,
                'rate' => 0.095,
            ],
        ];

        DB::table('tax_rates')->insert($dataTables);
    }
}
