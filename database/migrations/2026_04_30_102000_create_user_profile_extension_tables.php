<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * user_images, user_education_details, user_siblings_details, user_partner_preferences.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_images')) {
            Schema::create('user_images', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('image_type', 32)->comment('profile|gallery|verification|other');
                $table->string('image_url', 2048);
                $table->string('thumbnail_url', 2048)->nullable();
                $table->boolean('is_profile_photo')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('uploaded_by')->nullable()->comment('users.id when uploaded by staff or another user.');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'is_active']);
                $table->index(['user_id', 'is_profile_photo']);
            });
        }

        if (!Schema::hasTable('user_education_details')) {
            Schema::create('user_education_details', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('degree_id')->nullable()->constrained('degrees')->nullOnDelete();
                $table->string('field_of_study', 255)->nullable();
                $table->string('institution_name', 255)->nullable();
                $table->string('education_type', 32)->comment('school|diploma|graduation|post_graduation|doctorate|other');
                $table->unsignedSmallInteger('start_year')->nullable();
                $table->unsignedSmallInteger('end_year')->nullable();
                $table->string('grade_or_percentage', 64)->nullable();
                $table->boolean('is_highest')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'is_highest']);
            });
        }

        if (!Schema::hasTable('user_siblings_details')) {
            Schema::create('user_siblings_details', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 255);
                $table->string('gender', 32)->nullable();
                $table->string('relation_type', 16)->comment('brother|sister');
                $table->string('marital_status', 64)->nullable();
                $table->string('occupation', 255)->nullable();
                $table->string('education', 255)->nullable();
                $table->unsignedTinyInteger('age')->nullable();
                $table->boolean('is_elder')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('user_partner_preferences')) {
            Schema::create('user_partner_preferences', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('preferred_gender', 32)->nullable();
                $table->unsignedTinyInteger('preferred_min_age')->nullable();
                $table->unsignedTinyInteger('preferred_max_age')->nullable();
                $table->string('preferred_min_height', 32)->nullable();
                $table->string('preferred_max_height', 32)->nullable();
                $table->string('preferred_marital_status', 64)->nullable();
                $table->string('preferred_diet', 64)->nullable();
                $table->string('preferred_smoking', 32)->nullable();
                $table->string('preferred_drinking', 32)->nullable();
                $table->string('preferred_education', 255)->nullable();
                $table->string('preferred_occupation', 255)->nullable();
                $table->decimal('preferred_income_min', 14, 2)->nullable();
                $table->foreignId('preferred_country_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->foreignId('preferred_state_id')->nullable()->constrained('states')->nullOnDelete();
                $table->foreignId('preferred_city_id')->nullable()->constrained('cities')->nullOnDelete();
                $table->string('preferred_community', 255)->nullable();
                $table->foreignId('preferred_language_id')->nullable()->constrained('languages')->nullOnDelete();
                $table->text('preferred_other_criteria')->nullable();
                $table->timestamps();

                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_partner_preferences');
        Schema::dropIfExists('user_siblings_details');
        Schema::dropIfExists('user_education_details');
        Schema::dropIfExists('user_images');
    }
};
