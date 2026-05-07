<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_partner_preferences', function (Blueprint $table): void {
            if (Schema::hasColumn('user_partner_preferences', 'preferred_country_id')) {
                $table->dropConstrainedForeignId('preferred_country_id');
            }
            if (Schema::hasColumn('user_partner_preferences', 'preferred_state_id')) {
                $table->dropConstrainedForeignId('preferred_state_id');
            }
            if (Schema::hasColumn('user_partner_preferences', 'preferred_city_id')) {
                $table->dropConstrainedForeignId('preferred_city_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_partner_preferences', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_partner_preferences', 'preferred_country_id')) {
                $table->foreignId('preferred_country_id')->nullable()->constrained('countries')->nullOnDelete();
            }
            if (!Schema::hasColumn('user_partner_preferences', 'preferred_state_id')) {
                $table->foreignId('preferred_state_id')->nullable()->constrained('states')->nullOnDelete();
            }
            if (!Schema::hasColumn('user_partner_preferences', 'preferred_city_id')) {
                $table->foreignId('preferred_city_id')->nullable()->constrained('cities')->nullOnDelete();
            }
        });
    }
};

