<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * favorites, matches, contact_requests, profile_do_not_show, profile_views.
 * Doc `notifications` table skipped in favor of Laravel polymorphic notifications migration.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('favorites')) {
            Schema::create('favorites', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('favorite_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('source', 32)->nullable()->comment('browse|matches|profile');
                $table->timestamp('created_at')->useCurrent();
                $table->softDeletes();

                $table->unique(['user_id', 'favorite_user_id']);
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('matches')) {
            Schema::create('matches', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('matched_user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedSmallInteger('match_score')->nullable();
                $table->json('match_reason_json')->nullable();
                $table->string('match_status', 32)->default('active')->comment('active|hidden|removed');
                $table->string('generated_by', 32)->default('system')->comment('system|manual');
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'matched_user_id']);
                $table->index(['user_id', 'match_status']);
            });
        }

        if (!Schema::hasTable('contact_requests')) {
            Schema::create('contact_requests', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
                $table->text('request_message')->nullable();
                $table->string('request_status', 32)->default('pending')->comment('pending|accepted|rejected|cancelled');
                $table->timestamp('responded_at')->nullable();
                $table->text('response_message')->nullable();
                $table->timestamps();

                $table->index(['to_user_id', 'request_status']);
                $table->index(['from_user_id', 'request_status']);
            });
        }

        if (!Schema::hasTable('profile_do_not_show')) {
            Schema::create('profile_do_not_show', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('hidden_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('reason', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['user_id', 'hidden_user_id']);
            });
        }

        if (!Schema::hasTable('profile_views')) {
            Schema::create('profile_views', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('viewer_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('viewed_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('source', 32)->nullable()->comment('browse|matches|direct_link|favorites');
                $table->timestamp('viewed_at')->useCurrent();
                $table->string('device_type', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['viewed_user_id', 'viewed_at']);
                $table->index('viewer_user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
        Schema::dropIfExists('profile_do_not_show');
        Schema::dropIfExists('contact_requests');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('favorites');
    }
};
