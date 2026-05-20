<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\SeedingGuard;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /** @var bool $scoutQueue */
        $scoutQueue = config('scout.queue');

        SeedingGuard::run(function () use ($scoutQueue): void {
            /*
             * When true, Scout still indexes but avoids queue-backed sync jobs touching `jobs`.
             */
            config(['scout.queue' => false]);

            try {
                $this->call([
                    DemoMasterDataSeeder::class,
                    ChhattisgarhMasterGeoSeeder::class,
                    MasterDegreesOccupationsSeeder::class,
                    RbacSeeder::class,
                    PackageCatalogSeeder::class,
                    DemoAuthUsersSeeder::class,
                    DemoTeamUsersSeeder::class,
                    DemoUsersSeeder::class,
                    DemoCandidateNotificationsSeeder::class,
                    DemoSubscriptionPaymentSeeder::class,
                    DemoUserComplianceSeeder::class,
                ]);
            } finally {
                config(['scout.queue' => $scoutQueue]);
            }
        });
    }
}
