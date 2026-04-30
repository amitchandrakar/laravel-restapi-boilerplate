<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * settings, seo_settings, advertisements.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('group_key', 128);
                $table->string('setting_key', 128);
                $table->text('setting_value')->nullable();
                $table->string('value_type', 16)->default('string')->comment('string|number|boolean|json');
                $table->boolean('is_public')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['group_key', 'setting_key']);
                $table->index(['is_active', 'is_public']);
            });
        }

        if (!Schema::hasTable('seo_settings')) {
            Schema::create('seo_settings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('page_key', 128)->unique();
                $table->string('title', 512)->nullable();
                $table->text('description')->nullable();
                $table->text('keywords')->nullable();
                $table->string('canonical_url', 2048)->nullable();
                $table->string('og_title', 512)->nullable();
                $table->text('og_description')->nullable();
                $table->string('og_image_url', 2048)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('advertisements')) {
            Schema::create('advertisements', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('title');
                $table->string('ad_type', 32)->comment('banner|popup|inline');
                $table->string('placement', 64)->comment('home|browse|profile|matches|global');
                $table->string('image_url', 2048)->nullable();
                $table->string('redirect_url', 2048)->nullable();
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->unsignedSmallInteger('priority')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable()->comment('users.id');
                $table->timestamps();

                $table->index(['placement', 'is_active']);
                $table->index(['start_at', 'end_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
        Schema::dropIfExists('seo_settings');
        Schema::dropIfExists('settings');
    }
};
