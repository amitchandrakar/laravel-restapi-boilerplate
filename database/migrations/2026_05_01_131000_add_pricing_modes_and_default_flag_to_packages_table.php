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
            if (!Schema::hasColumn('packages', 'monthly_price')) {
                $table->decimal('monthly_price', 14, 2)->nullable()->after('discounted_price');
            }
            if (!Schema::hasColumn('packages', 'yearly_price')) {
                $table->decimal('yearly_price', 14, 2)->nullable()->after('monthly_price');
            }
            if (!Schema::hasColumn('packages', 'is_default_registration')) {
                $table
                    ->boolean('is_default_registration')
                    ->default(false)
                    ->after('is_active')
                    ->comment('Used to auto-assign package on new user registration.');
            }
        });

        DB::table('packages')
            ->orderBy('id')
            ->chunkById(100, function ($packages): void {
                foreach ($packages as $package) {
                    $yearly = (float) ($package->price ?? 0);
                    $monthly = $yearly > 0 ? round($yearly / 12, 2) : 0.0;
                    DB::table('packages')
                        ->where('id', $package->id)
                        ->update([
                            'yearly_price' => $yearly,
                            'monthly_price' => $monthly,
                        ]);
                }
            });

        $defaultPackageId = (int) DB::table('packages')->where('code', 'PARICHAY_FREE')->value('id');
        if ($defaultPackageId > 0) {
            DB::table('packages')
                ->where('id', $defaultPackageId)
                ->update(['is_default_registration' => true]);
        }

        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'monthly_price')) {
                $table->decimal('monthly_price', 14, 2)->default(0)->nullable(false)->change();
            }
            if (Schema::hasColumn('packages', 'yearly_price')) {
                $table->decimal('yearly_price', 14, 2)->default(0)->nullable(false)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            foreach (['is_default_registration', 'yearly_price', 'monthly_price'] as $column) {
                if (Schema::hasColumn('packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
