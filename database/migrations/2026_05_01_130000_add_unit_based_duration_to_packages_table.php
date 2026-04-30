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
            if (!Schema::hasColumn('packages', 'duration_value')) {
                $table->unsignedInteger('duration_value')->nullable()->after('duration_days');
            }
            if (!Schema::hasColumn('packages', 'duration_unit')) {
                $table->string('duration_unit', 16)->nullable()->after('duration_value')->comment('month|year');
            }
        });

        DB::table('packages')
            ->orderBy('id')
            ->chunkById(100, function ($packages): void {
                foreach ($packages as $package) {
                    $days = (int) ($package->duration_days ?? 0);
                    if ($days <= 0) {
                        DB::table('packages')
                            ->where('id', $package->id)
                            ->update([
                                'duration_value' => 1,
                                'duration_unit' => 'year',
                            ]);

                        continue;
                    }

                    if ($days % 365 === 0) {
                        DB::table('packages')
                            ->where('id', $package->id)
                            ->update([
                                'duration_value' => max(1, intdiv($days, 365)),
                                'duration_unit' => 'year',
                            ]);

                        continue;
                    }

                    DB::table('packages')
                        ->where('id', $package->id)
                        ->update([
                            'duration_value' => max(1, (int) round($days / 30)),
                            'duration_unit' => 'month',
                        ]);
                }
            });

        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'duration_value')) {
                $table->unsignedInteger('duration_value')->default(1)->nullable(false)->change();
            }
            if (Schema::hasColumn('packages', 'duration_unit')) {
                $table->string('duration_unit', 16)->default('year')->nullable(false)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'duration_unit')) {
                $table->dropColumn('duration_unit');
            }
            if (Schema::hasColumn('packages', 'duration_value')) {
                $table->dropColumn('duration_value');
            }
        });
    }
};
