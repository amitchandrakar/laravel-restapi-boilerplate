<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServingOptionsSeeder extends Seeder
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
                'name' => 'Standard No Cost',
                'description' => '<ul style="padding-left: 20px;">
                        <li>Medium-weight disposable black plates</li>
                        <li>Medium weight flatware</li>
                        <li>Alonti logo napkins&nbsp;</li>
                        <li>Disposable wire chafers&nbsp;</li>
                        <li>Black plastic serving utensils</li>
                    </ul>',
                'image' => 'standard.png',
                'price' => 0,
                'status' => 'active',
                'created_by' => null,
                'updated_by' => null,
                'created_at' => null,
                'updated_at' => null,
                'deleted_by' => null,
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'name' => 'Option 1 $2 Per Person',
                'description' => '<ul style="padding-left: 20px;">
                        <li>Heavy weight disposable clear plates</li>
                        <li>Heavy-weight flatware</li>
                        <li>Rolled no-logo napkins with linen-feel</li>
                        <li>Disposable wire chafers</li>
                        <li>Black plastic serving utensils</li>
                    </ul>',
                'image' => '2.png',
                'price' => 2,
                'status' => 'active',
                'created_by' => null,
                'updated_by' => null,
                'created_at' => null,
                'updated_at' => null,
                'deleted_by' => null,
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'name' => 'Option 2 $7 Per Person',
                'description' => '<ul style="padding-left: 20px;">
                        <li>Heavy-weight disposable clear plates</li>
                        <li>Heavy weight flatware</li>
                        <li>Rolled no-logo linen-feel napkins</li>
                        <li>Silver chafers</li>
                        <li>Metal serving utensils</li>
                        <li>Chafer and serving utensil pick-up after event</li>
                    </ul>',
                'image' => '7.png',
                'price' => 7,
                'status' => 'active',
                'created_by' => null,
                'updated_by' => null,
                'created_at' => null,
                'updated_at' => null,
                'deleted_by' => null,
                'deleted_at' => null,
            ],
        ];

        DB::table('serving_options')->insert($dataTables);
    }
}
