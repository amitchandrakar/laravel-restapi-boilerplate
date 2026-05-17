<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'phone_alerts_enabled')) {
                $table->boolean('phone_alerts_enabled')->default(false)->after('status');
            }
            if (!Schema::hasColumn('users', 'email_notifications_enabled')) {
                $table->boolean('email_notifications_enabled')->default(true)->after('phone_alerts_enabled');
            }
            if (!Schema::hasColumn('users', 'show_online_status')) {
                $table->boolean('show_online_status')->default(false)->after('email_notifications_enabled');
            }
            if (!Schema::hasColumn('users', 'hide_phone_number')) {
                $table->boolean('hide_phone_number')->default(true)->after('show_online_status');
            }
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 64)->nullable()->unique()->after('hide_phone_number');
            }
        });

        if (!Schema::hasTable('referral_entries')) {
            Schema::create('referral_entries', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('inviter_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('invitee_name', 255);
                $table->timestamp('invited_at')->useCurrent();
                $table->string('status', 32)->default('invited')->comment('invited|joined|rewardEligible');
                $table->timestamps();

                $table->index(['inviter_user_id', 'invited_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_entries');

        Schema::table('users', function (Blueprint $table): void {
            foreach (
                [
                    'phone_alerts_enabled',
                    'email_notifications_enabled',
                    'show_online_status',
                    'hide_phone_number',
                    'referral_code',
                ] as $col
            ) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
