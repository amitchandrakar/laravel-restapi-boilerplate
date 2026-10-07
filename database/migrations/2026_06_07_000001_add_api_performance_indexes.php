<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index('profile_status', 'users_profile_status_idx');
            $table->index('updated_at', 'users_updated_at_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index('payment_status', 'payments_payment_status_idx');
        });

        Schema::table('user_sessions', function (Blueprint $table): void {
            $table->index(['session_token_hash', 'is_active'], 'user_sessions_token_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_sessions', function (Blueprint $table): void {
            $table->dropIndex('user_sessions_token_active_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_payment_status_idx');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_profile_status_idx');
            $table->dropIndex('users_updated_at_idx');
        });
    }
};
