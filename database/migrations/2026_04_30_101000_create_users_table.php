<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core `users` table from docs/DATABASE_DESIGN.md.
 * Self-referencing audit columns (created_by / updated_by / deleted_by) are nullable integers without FK
 * to avoid circular migration constraints; enforce in application layer if needed.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('profile_id')->nullable()->comment('Optional link to extended profile record when introduced.');

            $table->string('first_name', 128);
            $table->string('middle_name', 128)->nullable();
            $table->string('last_name', 128);
            $table->string('gender', 32)->nullable()->comment('User-reported gender identity.');
            $table->string('body_type', 64)->nullable();
            $table->string('complexion', 64)->nullable();
            $table->string('height', 32)->nullable()->comment('Display height, e.g. 5ft 10in or cm.');
            $table->string('weight', 32)->nullable();
            $table->string('blood_group', 8)->nullable();

            $table->string('email')->unique();
            $table->string('phone', 32)->nullable();
            $table->string('password');

            $table->date('date_of_birth')->nullable();
            $table->time('time_of_birth')->nullable();
            $table->string('zodiac_sign', 32)->nullable();

            $table->string('place_of_birth_country', 128)->nullable();
            $table->string('place_of_birth_state', 128)->nullable();
            $table->string('place_of_birth_city', 128)->nullable();
            $table->string('place_of_birth_district', 128)->nullable();
            $table->string('place_of_birth_village', 128)->nullable();

            $table->string('current_country', 128)->nullable();
            $table->string('current_state', 128)->nullable();
            $table->string('current_city', 128)->nullable();
            $table->string('current_district', 128)->nullable();
            $table->string('current_village', 128)->nullable();

            $table->string('hometown_country', 128)->nullable();
            $table->string('hometown_state', 128)->nullable();
            $table->string('hometown_city', 128)->nullable();
            $table->string('hometown_district', 128)->nullable();
            $table->string('hometown_village', 128)->nullable();

            $table->string('occupation', 255)->nullable()->comment('Free-text occupation; see occupations master for structured data.');
            $table->string('employer', 255)->nullable();
            $table->decimal('income', 14, 2)->nullable()->comment('Annual or monthly income depending on product rules.');

            $table->string('father_name', 255)->nullable();
            $table->string('father_occupation', 255)->nullable();
            $table->string('father_gotra', 128)->nullable();
            $table->string('father_native_place', 255)->nullable();

            $table->string('mother_name', 255)->nullable();
            $table->string('mother_occupation', 255)->nullable();
            $table->string('mother_gotra', 128)->nullable();
            $table->string('mother_native_place', 255)->nullable();

            $table->unsignedTinyInteger('brothers_count')->nullable();
            $table->unsignedTinyInteger('sisters_count')->nullable();
            $table->string('family_type', 64)->nullable();
            $table->string('family_status', 64)->nullable();

            $table->string('diet', 64)->nullable()->comment('e.g. vegetarian, non_vegetarian, vegan.');
            $table->string('smoking', 32)->nullable()->comment('e.g. never, occasionally, regularly.');
            $table->string('drinking', 32)->nullable()->comment('e.g. never, socially, regularly.');

            $table->text('hobbies')->nullable();
            $table->text('interests')->nullable();
            $table->text('likes')->nullable();
            $table->text('dislikes')->nullable();

            $table->string('preferred_age_range', 64)->nullable()->comment('Display or filter range, e.g. 25-32.');
            $table->string('preferred_height_range', 64)->nullable();
            $table->text('preferred_hobbies')->nullable();
            $table->text('preferred_interests')->nullable();
            $table->text('preferred_likes')->nullable();
            $table->text('preferred_dislikes')->nullable();
            $table->text('preferred_other_criteria')->nullable();
            $table->string('preferred_education', 255)->nullable();
            $table->string('preferred_community', 255)->nullable();

            $table->string('status', 32)->default('active')->comment('Account lifecycle: active, suspended, pending_verification, etc.');
            $table->unsignedBigInteger('created_by')->nullable()->comment('users.id of creator when applicable.');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('users.id of last updater when applicable.');
            $table->unsignedBigInteger('deleted_by')->nullable()->comment('users.id of soft-delete actor when applicable.');

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
