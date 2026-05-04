<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoMasterDataSeeder::class,
            ChhattisgarhMasterGeoSeeder::class,
            MasterDegreesOccupationsSeeder::class,
            RbacSeeder::class,
            PackageCatalogSeeder::class,
            DemoAuthUsersSeeder::class,
            DemoTeamUsersSeeder::class,
            DemoUsersSeeder::class,
            DemoSubscriptionPaymentSeeder::class,
            DemoUserComplianceSeeder::class,
            DemoUserLogSeeder::class,
        ]);
    }
}
