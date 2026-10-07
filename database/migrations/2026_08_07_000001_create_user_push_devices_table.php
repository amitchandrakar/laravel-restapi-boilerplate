<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_push_devices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('fcm_token');
            $table->string('token_hash', 64);
            $table->string('platform', 32)->nullable()->comment('android|ios|web');
            $table->string('device_id', 255)->nullable();
            $table->string('app_version', 64)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'token_hash'], 'user_push_devices_user_token_hash_unique');
            $table->index('token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_push_devices');
    }
};
