<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * audit_logs, user_activity_logs, user_device_logs, user_sessions.
 * Distinct from Laravel framework `sessions` table.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('entity_type', 255);
                $table->unsignedBigInteger('entity_id');
                $table->string('action', 128);
                $table->json('old_values_json')->nullable();
                $table->json('new_values_json')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['entity_type', 'entity_id']);
                $table->index(['actor_user_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('user_activity_logs')) {
            Schema::create('user_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('activity_type', 128);
                $table->string('activity_source', 128)->nullable();
                $table->json('metadata_json')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['user_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('user_device_logs')) {
            Schema::create('user_device_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_id', 255)->nullable();
                $table->string('device_type', 64)->nullable();
                $table->string('device_name', 255)->nullable();
                $table->string('os_name', 64)->nullable();
                $table->string('os_version', 64)->nullable();
                $table->string('app_version', 64)->nullable();
                $table->text('push_token')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'device_id']);
            });
        }

        if (!Schema::hasTable('user_sessions')) {
            Schema::create('user_sessions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('session_token_hash', 255);
                $table->string('refresh_token_hash', 255)->nullable();
                $table->timestamp('login_at')->useCurrent();
                $table->timestamp('expires_at');
                $table->timestamp('logout_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('device_id', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'is_active']);
                $table->index('expires_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('user_device_logs');
        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('audit_logs');
    }
};
