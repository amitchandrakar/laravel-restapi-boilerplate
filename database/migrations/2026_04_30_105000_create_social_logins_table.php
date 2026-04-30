<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('social_logins')) {
            return;
        }

        Schema::create('social_logins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 32)->comment('google|facebook|apple');
            $table->string('provider_user_id', 255);
            $table->string('provider_email', 255)->nullable();
            $table->string('access_token_hash', 255)->nullable();
            $table->string('refresh_token_hash', 255)->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->index(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_logins');
    }
};
