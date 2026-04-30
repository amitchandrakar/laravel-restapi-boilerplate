<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'is_popular')) {
                $table->boolean('is_popular')->default(false)->after('is_default_registration');
            }
        });

        if (Schema::hasColumn('packages', 'duration_value')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn('duration_value');
            });
        }
        if (Schema::hasColumn('packages', 'duration_days')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn('duration_days');
            });
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `packages` MODIFY `duration_unit` ENUM('month','year') NOT NULL DEFAULT 'year'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE packages DROP CONSTRAINT IF EXISTS packages_duration_unit_check');
            DB::statement(
                "ALTER TABLE packages ADD CONSTRAINT packages_duration_unit_check CHECK (duration_unit IN ('month','year'))"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('packages', 'duration_unit')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `packages` MODIFY `duration_unit` VARCHAR(16) NOT NULL DEFAULT 'year'");
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE packages DROP CONSTRAINT IF EXISTS packages_duration_unit_check');
            }
        }

        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'duration_value')) {
                $table->unsignedInteger('duration_value')->default(1)->after('duration_unit');
            }
            if (!Schema::hasColumn('packages', 'duration_days')) {
                $table->unsignedInteger('duration_days')->default(365)->after('duration_value');
            }
            if (Schema::hasColumn('packages', 'is_popular')) {
                $table->dropColumn('is_popular');
            }
        });
    }
};
