<?php

declare(strict_types=1);

use Database\Seeders\RbacSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Ensures coupon + moderation-report permissions exist and are assigned to roles.
 * Safe to re-run: {@see RbacSeeder} uses updateOrCreate / syncPermissions.
 */
return new class extends Migration {
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => RbacSeeder::class,
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        // Permission rows are shared; do not remove on rollback.
    }
};
