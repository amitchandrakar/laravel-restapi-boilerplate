<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Community Connect schema (replaces 36 incremental migrations).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('alonti_users');

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary()->comment('Account email the reset link was sent to.');
            $table->string('token')->comment('Hashed or opaque reset token (never store plain passwords).');
            $table->timestamp('created_at')->nullable()->comment('When this reset row was created.');
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary()->comment('Session id (cookie).');
            $table->foreignId('user_id')->nullable()->index()->comment('Authenticated user, if any.');
            $table->string('ip_address', 45)->nullable()->comment('Client IP (v4 or v6).');
            $table->text('user_agent')->nullable()->comment('Raw User-Agent header.');
            $table->longText('payload')->comment('Serialized session data.');
            $table->integer('last_activity')->index()->comment('Unix timestamp of last request activity.');
        });

        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index()->comment('Queue name the job is assigned to.');
            $table->unsignedTinyInteger('attempts')->default(0)->comment('Number of execution attempts so far.');
            $table->unsignedInteger('reserved_at')->nullable()->comment('Unix time when a worker reserved this job.');
            $table->unsignedInteger('available_at')->comment('Unix time after which the job may run.');
            $table->unsignedInteger('created_at')->comment('Unix time when the job was queued.');
            $table->longText('payload')->comment('Serialized job payload (class + data).');
        });

        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique()->comment('Stable id for deduplication and UI.');
            $table->text('connection')->comment('Queue connection name.');
            $table->text('queue')->comment('Queue name the job failed on.');
            $table->longText('payload')->comment('Serialized failed job payload.');
            $table->longText('exception')->comment('Stack trace / error text.');
            $table->timestamp('failed_at')->useCurrent()->comment('When the failure was recorded.');
        });

        Schema::create('job_batches', function (Blueprint $table): void {
            $table->string('id')->primary()->comment('Batch UUID string.');
            $table->string('name')->comment('Human-readable batch label.');
            $table->integer('total_jobs')->comment('Jobs dispatched in this batch.');
            $table->integer('pending_jobs')->comment('Jobs not yet finished.');
            $table->integer('failed_jobs')->comment('Jobs that failed permanently.');
            $table->longText('failed_job_ids')->comment('JSON list of failed job ids.');
            $table->mediumText('options')->nullable()->comment('Optional batch options JSON.');
            $table->integer('cancelled_at')->nullable()->comment('Unix time if batch was cancelled.');
            $table->integer('created_at')->comment('Unix time batch was created.');
            $table->integer('finished_at')->nullable()->comment('Unix time batch completed.');
        });

        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('Display name of the country.');
            $table->string('iso2', 2)->nullable()->comment('ISO 3166-1 alpha-2 country code.');
            $table->string('iso3', 3)->nullable()->comment('ISO 3166-1 alpha-3 country code.');
            $table->string('phone_code', 16)->nullable()->comment('International dialing prefix, e.g. +91.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('iso2');
            $table->index('is_active');
        });

        Schema::create('states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete()->comment('Parent country.');
            $table->string('name')->comment('State or province display name.');
            $table->string('code', 32)->nullable()->comment('State/province code within country.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_id', 'is_active']);
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete()->comment('Parent state.');
            $table->string('name')->comment('City or town display name.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['state_id', 'is_active']);
        });

        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('Language display name.');
            $table->string('code', 32)->nullable()->comment('BCP 47 / internal language code.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code');
            $table->index('is_active');
        });

        Schema::create('degrees', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('Degree or qualification label.');
            $table->string('degree_type', 64)->nullable()->comment('e.g. undergraduate, postgraduate.');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('occupations', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('Occupation or job title label.');
            $table->string('category', 128)->nullable()->comment('High-level occupation grouping.');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('income_ranges', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('Bracket label shown in UI (e.g. "5–10 LPA").');
            $table->decimal('min_amount', 14, 2)->nullable()->comment('Lower bound inclusive; null = no lower limit.');
            $table->decimal('max_amount', 14, 2)->nullable()->comment('Upper bound inclusive; null = no upper limit.');
            $table->string('currency', 8)->default('INR')->comment('ISO 4217 code for min/max amounts.');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('surnames', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('Community surname allowed at registration.');
            $table
                ->foreignId('language_id')
                ->nullable()
                ->constrained('languages')
                ->nullOnDelete()
                ->comment('Optional language grouping.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['language_id', 'is_active']);
        });

        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique()->comment('Snake_case id, e.g. admin_candidates.');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
            $table->index('is_active');
        });

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        if (empty($tableNames)) {
            throw new RuntimeException('Error: config/permission.php not loaded.');
        }

        Schema::create($tableNames['permissions'], static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name')->comment('Machine-readable permission name (unique per guard).');
            $table->string('guard_name')->comment('Auth guard this permission applies to (e.g. web, sanctum).');
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->string('action', 16)->nullable()->comment('view|add|edit|delete');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name')->comment('Machine-readable role name (unique per guard).');
            $table->string('guard_name')->comment('Auth guard this role applies to.');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_default_registration')->default(false);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use (
            $tableNames,
            $columnNames,
            $pivotPermission
        ): void {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index(
                [$columnNames['model_morph_key'], 'model_type'],
                'model_has_permissions_model_id_model_type_index'
            );
            $table->foreign($pivotPermission)->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            $table->primary(
                [$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use (
            $tableNames,
            $columnNames,
            $pivotRole
        ): void {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign($pivotRole)->references('id')->on($tableNames['roles'])->onDelete('cascade');
            $table->primary(
                [$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use (
            $tableNames,
            $pivotRole,
            $pivotPermission
        ): void {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);
            $table->foreign($pivotPermission)->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            $table->foreign($pivotRole)->references('id')->on($tableNames['roles'])->onDelete('cascade');
            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table
                ->unsignedBigInteger('profile_id')
                ->nullable()
                ->comment('Optional link to extended profile record when introduced.');

            $table->string('first_name', 128)->comment('Legal or preferred given name.');
            $table->string('middle_name', 128)->nullable()->comment('Optional middle name.');
            $table->string('last_name', 128)->comment('Typically surname shown on profile.');
            $table->string('gender', 32)->nullable()->comment('User-reported gender identity.');
            $table->string('marital_status', 64)->nullable()->comment('e.g. single, divorced, widowed.');
            $table->string('body_type', 64)->nullable()->comment('Physique classification for matching.');
            $table->string('complexion', 64)->nullable()->comment('Skin tone / complexion label.');
            $table->string('height', 32)->nullable()->comment('Display height, e.g. 5ft 10in or cm.');
            $table->string('weight', 32)->nullable()->comment('Body weight with unit implicit in UX.');
            $table->string('blood_group', 8)->nullable()->comment('Blood type, e.g. O+.');
            $table->string('manglik_status', 32)->nullable()->comment('Horoscope matching flag for partner search.');
            $table->text('about_me')->nullable()->comment('Free-text self-summary.');

            $table->string('sub_caste', 128)->nullable()->comment('Sub-caste or division within community.');
            $table->string('gotra', 128)->nullable()->comment('Gotra / lineage label.');
            $table->string('rashi', 32)->nullable()->comment('Moon sign (Vedic).');
            $table->string('nakshatra', 64)->nullable()->comment('Birth nakshatra (Vedic lunar mansion).');

            $table->string('email')->nullable()->unique()->comment('Login identifier; nullable if phone-only account.');
            $table->string('phone', 32)->nullable()->comment('E.164 or local phone; used for login and OTP.');
            $table->string('password')->comment('Bcrypt/argon hash of account password.');

            $table->date('date_of_birth')->nullable()->comment('Used for age and horoscope.');
            $table->time('time_of_birth')->nullable()->comment('For detailed horoscope.');
            $table->string('zodiac_sign', 32)->nullable()->comment('Western or mapped zodiac label.');
            $table->string('place_of_birth_line', 255)->nullable()->comment('Unstructured birthplace text.');

            $table->string('place_of_birth_country', 128)->nullable()->comment('Denormalized birth country label.');
            $table->string('place_of_birth_state', 128)->nullable()->comment('Denormalized birth state label.');
            $table->string('place_of_birth_city', 128)->nullable()->comment('Denormalized birth city label.');
            $table
                ->foreignId('birth_country_id')
                ->nullable()
                ->constrained('countries')
                ->nullOnDelete()
                ->comment('FK for structured birth geo.');
            $table
                ->foreignId('birth_state_id')
                ->nullable()
                ->constrained('states')
                ->nullOnDelete()
                ->comment('FK for structured birth geo.');
            $table
                ->foreignId('birth_city_id')
                ->nullable()
                ->constrained('cities')
                ->nullOnDelete()
                ->comment('FK for structured birth geo.');

            $table->string('current_country', 128)->nullable()->comment('Denormalized current residence country.');
            $table->string('current_state', 128)->nullable()->comment('Denormalized current residence state.');
            $table->string('current_city', 128)->nullable()->comment('Denormalized current residence city.');
            $table->string('current_district', 128)->nullable()->comment('Denormalized current residence district.');
            $table->string('current_village', 128)->nullable()->comment('Denormalized current residence village.');
            $table
                ->foreignId('current_country_id')
                ->nullable()
                ->constrained('countries')
                ->nullOnDelete()
                ->comment('FK for current residence (country).');
            $table
                ->foreignId('current_state_id')
                ->nullable()
                ->constrained('states')
                ->nullOnDelete()
                ->comment('FK for current residence (state).');
            $table
                ->foreignId('current_city_id')
                ->nullable()
                ->constrained('cities')
                ->nullOnDelete()
                ->comment('FK for current residence (city).');

            $table->string('hometown_country', 128)->nullable()->comment('Denormalized family hometown country.');
            $table->string('hometown_state', 128)->nullable()->comment('Denormalized family hometown state.');
            $table->string('hometown_city', 128)->nullable()->comment('Denormalized family hometown city.');
            $table->string('hometown_district', 128)->nullable()->comment('Denormalized family hometown district.');
            $table->string('hometown_village', 128)->nullable()->comment('Denormalized family hometown village.');
            $table
                ->foreignId('maternal_country_id')
                ->nullable()
                ->constrained('countries')
                ->nullOnDelete()
                ->comment('Maternal family roots (country).');
            $table
                ->foreignId('maternal_state_id')
                ->nullable()
                ->constrained('states')
                ->nullOnDelete()
                ->comment('Maternal family roots (state).');
            $table
                ->foreignId('maternal_city_id')
                ->nullable()
                ->constrained('cities')
                ->nullOnDelete()
                ->comment('Maternal family roots (city).');
            $table->string('maternal_village_name')->nullable()->comment('Maternal family roots (village).');

            $table
                ->string('occupation', 255)
                ->nullable()
                ->comment('Free-text occupation; see occupations master for structured data.');
            $table
                ->foreignId('occupation_id')
                ->nullable()
                ->constrained('occupations')
                ->nullOnDelete()
                ->comment('Structured occupation FK.');
            $table->string('employer', 255)->nullable()->comment('Company or organization name.');
            $table
                ->decimal('income', 14, 2)
                ->nullable()
                ->comment('Annual or monthly income depending on product rules.');
            $table
                ->foreignId('income_range_id')
                ->nullable()
                ->constrained('income_ranges')
                ->nullOnDelete()
                ->comment('Income bracket for discovery filters.');

            $table->string('father_name', 255)->nullable()->comment('Father full name.');
            $table->string('father_occupation', 255)->nullable()->comment('Father occupation label.');
            $table->string('father_gotra', 128)->nullable()->comment('Father lineage / gotra.');
            $table->string('father_native_place', 255)->nullable()->comment('Father native place (text).');

            $table->string('mother_name', 255)->nullable()->comment('Mother full name.');
            $table->string('mother_occupation', 255)->nullable()->comment('Mother occupation label.');
            $table->string('mother_gotra', 128)->nullable()->comment('Mother lineage / gotra.');
            $table->string('mother_native_place', 255)->nullable()->comment('Mother native place (text).');

            $table->unsignedTinyInteger('brothers_count')->nullable()->comment('Number of brothers.');
            $table->unsignedTinyInteger('sisters_count')->nullable()->comment('Number of sisters.');
            $table->string('family_type', 64)->nullable()->comment('e.g. nuclear, joint.');
            $table->string('family_status', 64)->nullable()->comment('e.g. middle_class, affluent.');

            $table->string('diet', 64)->nullable()->comment('e.g. vegetarian, non_vegetarian, vegan.');
            $table->string('smoking', 32)->nullable()->comment('e.g. never, occasionally, regularly.');
            $table->string('drinking', 32)->nullable()->comment('e.g. never, socially, regularly.');

            $table->string('sleep_pattern', 64)->nullable();
            $table->string('working_hours', 64)->nullable();
            $table->string('social_personality', 32)->nullable();
            $table->string('dietary_preferences', 64)->nullable();
            $table->string('drinking_habits', 32)->nullable();
            $table->string('smoking_habits', 32)->nullable();
            $table->string('fitness_level', 64)->nullable();
            $table->string('travel_style', 64)->nullable();
            $table->string('communication_style', 64)->nullable();
            $table->string('relationship_with_family', 64)->nullable();
            $table->string('weekend_preference', 64)->nullable();
            $table->json('interests')->nullable()->comment('List of interest tags.');
            $table->json('movie_genres')->nullable()->comment('Preferred movie genres.');
            $table->json('hobbies')->nullable()->comment('Hobby labels.');
            $table->json('likes')->nullable()->comment('Things the member likes.');
            $table->json('dislikes')->nullable()->comment('Things the member dislikes.');

            $table->string('preferred_age_range', 64)->nullable()->comment('Display or filter range, e.g. 25-32.');
            $table->string('preferred_height_range', 64)->nullable();
            $table->text('preferred_other_criteria')->nullable();

            $table
                ->string('status', 32)
                ->default('active')
                ->comment('Account lifecycle: active, suspended, pending_verification, etc.');
            $table->boolean('phone_alerts_enabled')->default(false);
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('show_online_status')->default(false);
            $table->boolean('hide_phone_number')->default(true);
            $table->string('referral_code', 64)->nullable()->unique();
            $table->unsignedBigInteger('role_id')->nullable()->comment('roles.id primary staff/candidate role.');
            $table->string('department', 128)->nullable()->comment('Team user department.');
            $table->string('job_title', 128)->nullable()->comment('Team user designation.');
            $table
                ->string('profile_photo_url', 2048)
                ->nullable()
                ->comment('Legacy primary photo URL; gallery uses user_images.');
            $table
                ->string('profile_status', 20)
                ->default('draft')
                ->comment('draft|under_review|published|suspended|spam');
            $table
                ->json('completed_sections_json')
                ->nullable()
                ->comment('Which profile wizard sections are marked complete.');
            $table->timestamp('published_at')->nullable()->comment('When candidate profile went live.');
            $table->boolean('is_featured')->default(false)->comment('Pinned on featured carousel.');
            $table->timestamp('featured_at')->nullable()->comment('When featured flag was set.');
            $table->unsignedBigInteger('featured_by')->nullable()->comment('users.id');
            $table->unsignedBigInteger('created_by')->nullable()->comment('users.id of creator when applicable.');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('users.id of last updater when applicable.');
            $table
                ->unsignedBigInteger('deleted_by')
                ->nullable()
                ->comment('users.id of soft-delete actor when applicable.');

            $table->timestamps();
            $table->softDeletes();

            $table->index('phone');
            $table->index('status');
            $table->index('role_id');
            $table->index(['status', 'role_id']);
            $table->index('created_at');
            $table->index(['is_featured', 'profile_status', 'published_at'], 'users_featured_list_idx');
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
            $table->foreign('featured_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('sessions', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('user_images', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('image_type', 32)->comment('profile|gallery|verification|other');
            $table->string('image_url', 2048)->comment('Public HTTP(S) URL to full image.');
            $table
                ->string('image_storage_path', 2048)
                ->nullable()
                ->comment('Object key/path in private storage when applicable.');
            $table->string('thumbnail_url', 2048)->nullable()->comment('CDN or thumb URL for grids.');
            $table->string('icon_url', 2048)->nullable()->comment('Square icon variant URL.');
            $table->boolean('is_profile_photo')->default(false)->comment('Whether this row is the avatar.');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Gallery ordering (lower first).');
            $table->boolean('is_active')->default(true);
            $table
                ->unsignedBigInteger('uploaded_by')
                ->nullable()
                ->comment('users.id when uploaded by staff or another user.');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'is_profile_photo']);
        });

        Schema::create('user_education_details', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table
                ->foreignId('degree_id')
                ->nullable()
                ->constrained('degrees')
                ->nullOnDelete()
                ->comment('Highest or row-specific degree.');
            $table->string('field_of_study', 255)->nullable()->comment('Major or specialization.');
            $table->string('institution_name', 255)->nullable()->comment('School or college name.');
            $table->string('education_type', 32)->comment('school|diploma|graduation|post_graduation|doctorate|other');
            $table->unsignedSmallInteger('start_year')->nullable()->comment('Enrollment start year.');
            $table->unsignedSmallInteger('end_year')->nullable()->comment('Completion year.');
            $table->string('grade_or_percentage', 64)->nullable()->comment('Marks or GPA display.');
            $table->boolean('is_highest')->default(false)->comment('Marks the top qualification row.');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_highest']);
        });

        Schema::create('user_siblings_details', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('gender', 32)->nullable();
            $table->string('relation_type', 16)->comment('brother|sister');
            $table->string('marital_status', 64)->nullable();
            $table->string('occupation', 255)->nullable();
            $table->string('education', 255)->nullable();
            $table->unsignedTinyInteger('age')->nullable()->comment('Approximate age at profile time.');
            $table->boolean('is_elder')->default(false)->comment('Older sibling flag for ordering.');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Display order among siblings.');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });

        Schema::create('user_partner_preferences', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('preferred_gender', 32)->nullable()->comment('Partner gender filter.');
            $table->unsignedTinyInteger('preferred_min_age')->nullable()->comment('Minimum acceptable partner age.');
            $table->unsignedTinyInteger('preferred_max_age')->nullable()->comment('Maximum acceptable partner age.');
            $table->string('preferred_min_height', 32)->nullable()->comment('Minimum height (display string).');
            $table->string('preferred_max_height', 32)->nullable()->comment('Maximum height (display string).');
            $table
                ->unsignedSmallInteger('preferred_min_weight')
                ->nullable()
                ->comment('Minimum weight in kg (integer).');
            $table
                ->unsignedSmallInteger('preferred_max_weight')
                ->nullable()
                ->comment('Maximum weight in kg (integer).');
            $table->string('preferred_body_type', 64)->nullable()->comment('Physique preference.');
            $table->string('preferred_complexion', 64)->nullable()->comment('Complexion preference.');
            $table->string('preferred_marital_status', 64)->nullable()->comment('Acceptable marital statuses.');
            $table->string('preferred_diet', 64)->nullable()->comment('Diet preference.');
            $table->string('preferred_smoking', 32)->nullable()->comment('Smoking tolerance.');
            $table->string('preferred_drinking', 32)->nullable()->comment('Drinking tolerance.');
            $table->json('preferred_degree_ids')->nullable()->comment('JSON array of degrees.id values.');
            $table
                ->json('preferred_community_ids')
                ->nullable()
                ->comment('JSON array of surnames.id (community) values.');
            $table->string('preferred_caste', 255)->nullable()->comment('Free-text caste preference.');
            $table->string('preferred_occupation', 255)->nullable()->comment('Partner occupation keyword.');
            $table->decimal('preferred_income_min', 14, 2)->nullable()->comment('Minimum expected partner income.');
            $table
                ->foreignId('preferred_language_id')
                ->nullable()
                ->constrained('languages')
                ->nullOnDelete()
                ->comment('Preferred spoken language.');
            $table->text('preferred_other_criteria')->nullable()->comment('Unstructured extra filters.');
            $table->string('preferred_sleep_pattern', 64)->nullable();
            $table->string('preferred_working_hours', 64)->nullable();
            $table->string('preferred_social_personality', 32)->nullable();
            $table->string('preferred_dietary_preferences', 64)->nullable();
            $table->string('preferred_drinking_habits', 32)->nullable();
            $table->string('preferred_smoking_habits', 32)->nullable();
            $table->string('preferred_fitness_level', 64)->nullable();
            $table->string('preferred_travel_style', 64)->nullable();
            $table->string('preferred_communication_style', 64)->nullable();
            $table->string('preferred_relationship_with_family', 64)->nullable();
            $table->string('preferred_weekend_preference', 64)->nullable();
            $table->json('preferred_interests')->nullable()->comment('Partner interest tags.');
            $table->json('preferred_movie_genres')->nullable();
            $table->json('preferred_hobbies')->nullable();
            $table->json('preferred_likes')->nullable();
            $table->json('preferred_dislikes')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('user_partner_preferred_locations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table
                ->foreignId('country_id')
                ->constrained('countries')
                ->cascadeOnDelete()
                ->comment('Region bucket; cascading keeps FK valid if master geo is rebuilt.');
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete()->comment('State-level preference.');
            $table
                ->foreignId('city_id')
                ->nullable()
                ->constrained('cities')
                ->nullOnDelete()
                ->comment('Optional city pin; null = whole state/country.');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Priority order shown in UI.');
            $table->timestamps();

            $table->unique(['user_id', 'country_id', 'state_id', 'city_id'], 'partner_pref_loc_user_geo_unique');
            $table->index('user_id');
        });

        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->text('description')->nullable();
            $table->string('duration_unit', 16)->default('year')->comment('month|year');
            $table->decimal('price', 14, 2)->comment('List or base price for the plan.');
            $table->decimal('discounted_price', 14, 2)->nullable()->comment('Promotional price when set.');
            $table->decimal('monthly_price', 14, 2)->default(0)->comment('Normalized monthly equivalent.');
            $table->decimal('yearly_price', 14, 2)->default(0)->comment('Normalized yearly equivalent.');
            $table->string('currency', 8)->default('INR');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default_registration')->default(false)->comment('Auto-assign on registration.');
            $table->boolean('is_popular')->default(false)->comment('Highlight in catalog UI.');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Display order in listings.');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Admin user who created the package.');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Admin user who last updated the package.');
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('package_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['package_id', 'permission_id']);
            $table->index('permission_id');
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->string('subscription_status', 32)->default('pending')->comment('active|expired|cancelled|pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->string('renewal_source', 32)->nullable()->comment('manual|gateway|admin');
            $table->unsignedBigInteger('last_payment_id')->nullable()->comment('payments.id when known; no DB FK.');
            $table->timestamps();

            $table->index(['user_id', 'subscription_status']);
            $table->index('ends_at');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->string('gateway_name', 64)->nullable()->comment('e.g. razorpay, stripe.');
            $table->string('gateway_order_id', 255)->nullable()->comment('Provider order/session id.');
            $table->string('gateway_payment_id', 255)->nullable()->comment('Provider payment/charge id.');
            $table->string('gateway_reference_id', 255)->nullable()->comment('Additional provider reference.');
            $table->decimal('amount', 14, 2)->comment('Captured or authorized amount.');
            $table->string('currency', 8)->default('INR')->comment('ISO 4217.');
            $table
                ->string('payment_status', 32)
                ->default('pending')
                ->comment('pending|success|failed|refunded|cancelled');
            $table->string('payment_method', 32)->nullable()->comment('upi|card|netbanking|wallet|cash|manual');
            $table->timestamp('paid_at')->nullable()->comment('When gateway confirmed success.');
            $table->text('failed_reason')->nullable()->comment('Gateway or internal failure message.');
            $table->json('raw_response_json')->nullable()->comment('Full gateway payload for audits.');
            $table->string('webhook_event_id', 64)->nullable()->unique()->comment('Idempotency key from webhooks.');
            $table->timestamps();

            $table->index(['user_id', 'payment_status']);
            $table->index('paid_at');
        });

        Schema::create('user_membership_history', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('action_type', 32)->comment('started|renewed|upgraded|downgraded|expired|cancelled');
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table
                ->foreignId('action_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Staff user; null means system.');
            $table->string('action_source', 32)->comment('user|system|admin');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_payment_history', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('history_type', 32)->comment('initiated|confirmed|failed|refund_initiated|refunded');
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payment_id', 'created_at']);
        });

        Schema::create('user_verification_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_type', 32)->comment('aadhaar|pan|passport|voter_id|driving_license|other');
            $table->string('document_number_masked', 255)->nullable();
            $table->string('document_front_url', 2048)->nullable();
            $table->string('document_back_url', 2048)->nullable();
            $table->string('selfie_url', 2048)->nullable();
            $table
                ->string('verification_status', 32)
                ->default('pending')
                ->comment('pending|approved|rejected|resubmission_required');
            $table->unsignedBigInteger('verified_by')->nullable()->comment('users.id of reviewer.');
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'verification_status']);
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('deleted_accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 128)->nullable();
            $table->text('reason_notes')->nullable();
            $table->boolean('deleted_by_user')->default(false);
            $table->boolean('deleted_by_admin')->default(false);
            $table->string('deleted_ip', 45)->nullable();
            $table->text('deleted_user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
        });

        Schema::create('data_erasure_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('request_type', 32)->comment('soft_delete|hard_delete|anonymize');
            $table->string('status', 32)->default('requested')->comment('requested|in_review|completed|rejected');
            $table->timestamp('requested_at')->useCurrent();
            $table
                ->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Reviewer who completed the request.');
            $table->timestamp('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('otp_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 16)->comment('sms|email|whatsapp');
            $table->string('destination', 255);
            $table->string('otp_hash', 255);
            $table->string('purpose', 32)->comment('register|login|phone_verify|email_verify|password_reset');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(5);
            $table->string('status', 32)->default('sent')->comment('sent|verified|expired|failed|blocked');
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['destination', 'purpose', 'status']);
            $table->index('expires_at');
        });

        Schema::create('social_logins', function (Blueprint $table): void {
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

        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('site_name', 255)->nullable();
            $table->text('logo_url')->nullable();
            $table->text('favicon_url')->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('contact_phone', 64)->nullable();
            $table->text('contact_address')->nullable();
            $table->json('allowed_community_surnames')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->boolean('require_profile_approval')->default(false);
            $table->unsignedInteger('success_stories_count')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('seo_global_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('site_title', 512)->nullable();
            $table->text('default_description')->nullable();
            $table->text('default_keywords')->nullable();
            $table->string('canonical_base_url', 2048)->nullable();
            $table->boolean('google_analytics_enabled')->default(false);
            $table->text('google_analytics_snippet')->nullable();
            $table->boolean('robots_enabled')->default(false);
            $table->text('robots_txt')->nullable();
            $table->boolean('sitemap_enabled')->default(false);
            $table->text('sitemap_urls')->nullable();
            $table->string('og_image', 2048)->nullable();
            $table->string('og_type', 64)->nullable();
            $table->string('twitter_card', 64)->nullable();
            $table->string('twitter_title', 512)->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image', 2048)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('social_login_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->boolean('google_enabled')->default(false);
            $table->string('google_environment', 32)->default('live');
            $table->string('google_live_client_id', 512)->nullable();
            $table->text('google_live_client_secret')->nullable();
            $table->string('google_live_redirect_url', 2048)->nullable();
            $table->boolean('facebook_enabled')->default(false);
            $table->string('facebook_environment', 32)->default('live');
            $table->string('facebook_live_client_id', 512)->nullable();
            $table->text('facebook_live_client_secret')->nullable();
            $table->string('facebook_live_redirect_url', 2048)->nullable();
            $table->boolean('instagram_enabled')->default(false);
            $table->string('instagram_environment', 32)->default('live');
            $table->string('instagram_live_client_id', 512)->nullable();
            $table->text('instagram_live_client_secret')->nullable();
            $table->string('instagram_live_redirect_url', 2048)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_gateway_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('gateway', 64)->default('razorpay');
            $table->boolean('is_enabled')->default(false);
            $table->string('environment', 32)->default('sandbox');
            $table->string('live_key_id', 255)->nullable();
            $table->text('live_key_secret')->nullable();
            $table->string('sandbox_key_id', 255)->nullable();
            $table->text('sandbox_key_secret')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->string('currency', 8)->default('INR');
            $table->text('checkout_options_json')->nullable();
            $table->string('webhook_url', 2048)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('notification_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->boolean('email_enabled')->default(false);
            $table->string('mail_mailer', 64)->nullable();
            $table->string('mail_host', 255)->nullable();
            $table->unsignedSmallInteger('mail_port')->nullable();
            $table->string('mail_username', 255)->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_encryption', 32)->nullable();
            $table->string('mail_from_address', 255)->nullable();
            $table->string('mail_from_name', 255)->nullable();
            $table->string('mail_reply_to_address', 255)->nullable();
            $table->string('mail_reply_to_name', 255)->nullable();
            $table->boolean('sms_enabled')->default(false);
            $table->string('twilio_account_sid', 255)->nullable();
            $table->text('twilio_auth_token')->nullable();
            $table->string('twilio_from_number', 64)->nullable();
            $table->boolean('push_enabled')->default(false);
            $table->text('fcm_server_key')->nullable();
            $table->string('fcm_sender_id', 128)->nullable();
            $table->text('fcm_client_key')->nullable();
            $table->string('fcm_topic', 128)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('storage_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->string('driver', 32)->default('s3');
            $table->string('bucket', 255)->nullable();
            $table->string('region', 64)->nullable();
            $table->string('access_key_id', 255)->nullable();
            $table->text('secret_access_key')->nullable();
            $table->string('endpoint', 2048)->nullable();
            $table->string('url', 2048)->nullable();
            $table->boolean('use_path_style_endpoint')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('redis_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->string('client', 32)->default('predis');
            $table->string('host', 255)->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('username', 128)->nullable();
            $table->text('password')->nullable();
            $table->unsignedTinyInteger('database')->default(0);
            $table->boolean('use_tls')->default(true);
            $table->string('cache_prefix', 128)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('search_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->string('driver', 64)->default('algolia');
            $table->string('app_id', 128)->nullable();
            $table->text('admin_api_key')->nullable();
            $table->text('search_api_key')->nullable();
            $table->string('candidate_index_name', 128)->nullable();
            $table->boolean('queue_indexing')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('seo_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('page_key', 128)->unique()->comment('Stable route or screen key.');
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

        Schema::create('favorites', function (Blueprint $table): void {
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

        Schema::create('matches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Member who owns this match row.');
            $table
                ->foreignId('matched_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Opposite party in suggested pair.');
            $table
                ->unsignedSmallInteger('match_score')
                ->nullable()
                ->comment('Ranking score 0–100 when algorithm fills.');
            $table->json('match_reason_json')->nullable();
            $table->string('match_status', 32)->default('active')->comment('active|hidden|removed');
            $table->string('generated_by', 32)->default('system')->comment('system|manual');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'matched_user_id']);
            $table->index(['user_id', 'match_status']);
        });

        Schema::create('contact_requests', function (Blueprint $table): void {
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

        Schema::create('profile_do_not_show', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hidden_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'hidden_user_id']);
        });

        Schema::create('profile_views', function (Blueprint $table): void {
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

        Schema::create('profile_spam_reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table
                ->foreignId('reporter_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Who filed the report.');
            $table
                ->foreignId('reported_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Profile subject of the report.');
            $table->text('reason')->nullable()->comment('Reporter explanation.');
            $table->string('status', 32)->default('pending')->comment('pending|reviewing|action_taken|dismissed');
            $table
                ->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Moderator who closed the case.');
            $table->timestamp('reviewed_at')->nullable()->comment('When moderation decision was recorded.');
            $table->text('admin_notes')->nullable()->comment('Internal moderator notes.');
            $table->timestamps();

            $table->index(['reported_user_id', 'status']);
            $table->index(['reporter_user_id', 'created_at']);
        });

        Schema::create('legal_pages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique()->comment('URL key, e.g. terms, privacy-policy.');
            $table->string('title')->comment('Page heading.');
            $table->longText('body')->comment('HTML or markdown body per app convention.');
            $table->string('version', 32)->nullable()->comment('Optional semantic version label.');
            $table->boolean('is_published')->default(false)->comment('Whether publicly visible.');
            $table->timestamp('published_at')->nullable()->comment('Go-live timestamp.');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('Last editor.');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug', 'is_published']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
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

        Schema::create('user_activity_logs', function (Blueprint $table): void {
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

        Schema::create('user_device_logs', function (Blueprint $table): void {
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

        Schema::create('user_sessions', function (Blueprint $table): void {
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

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('Notification instance UUID.');
            $table->string('type')->comment('Fully-qualified Laravel notification class name.');
            $table->morphs('notifiable');
            $table->text('data')->comment('JSON payload serialized by Laravel.');
            $table->timestamp('read_at')->nullable()->comment('When recipient acknowledged/read.');
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name')->comment('Human-readable token label (device/session name).');
            $table->string('token', 64)->unique()->comment('Hashed token suffix.');
            $table->text('abilities')->nullable()->comment('Optional SPA-delivered scopes.');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        $schema = Schema::connection($this->getConnection());

        $schema->create('telescope_entries', function (Blueprint $table) {
            $table->bigIncrements('sequence');
            $table->uuid('uuid');
            $table->uuid('batch_id');
            $table->string('family_hash')->nullable();
            $table->boolean('should_display_on_index')->default(true);
            $table->string('type', 20);
            $table->longText('content');
            $table->dateTime('created_at')->nullable();

            $table->unique('uuid');
            $table->index('batch_id');
            $table->index('family_hash');
            $table->index('created_at');
            $table->index(['type', 'should_display_on_index']);
        });

        $schema->create('telescope_entries_tags', function (Blueprint $table) {
            $table->uuid('entry_uuid');
            $table->string('tag');

            $table->primary(['entry_uuid', 'tag']);
            $table->index('tag');

            $table->foreign('entry_uuid')->references('uuid')->on('telescope_entries')->cascadeOnDelete();
        });

        $schema->create('telescope_monitoring', function (Blueprint $table) {
            $table->string('tag')->primary();
        });
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('user_device_logs');
        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('legal_pages');
        Schema::dropIfExists('profile_spam_reports');
        Schema::dropIfExists('referral_entries');
        Schema::dropIfExists('profile_views');
        Schema::dropIfExists('profile_do_not_show');
        Schema::dropIfExists('contact_requests');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('seo_settings');
        Schema::dropIfExists('search_settings');
        Schema::dropIfExists('redis_settings');
        Schema::dropIfExists('storage_settings');
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('payment_gateway_settings');
        Schema::dropIfExists('social_login_settings');
        Schema::dropIfExists('seo_global_settings');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('social_logins');
        Schema::dropIfExists('otp_requests');
        Schema::dropIfExists('data_erasure_requests');
        Schema::dropIfExists('deleted_accounts');
        Schema::dropIfExists('user_verification_documents');
        Schema::dropIfExists('user_payment_history');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('user_membership_history');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('package_permissions');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('user_partner_preferred_locations');
        Schema::dropIfExists('user_partner_preferences');
        Schema::dropIfExists('user_siblings_details');
        Schema::dropIfExists('user_education_details');
        Schema::dropIfExists('user_images');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');

        if (!empty($tableNames)) {
            Schema::dropIfExists($tableNames['role_has_permissions']);
            Schema::dropIfExists($tableNames['model_has_roles']);
            Schema::dropIfExists($tableNames['model_has_permissions']);
            Schema::dropIfExists($tableNames['roles']);
            Schema::dropIfExists($tableNames['permissions']);
        }

        Schema::dropIfExists('modules');
        Schema::dropIfExists('surnames');
        Schema::dropIfExists('income_ranges');
        Schema::dropIfExists('occupations');
        Schema::dropIfExists('degrees');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('telescope_entries_tags');
        Schema::dropIfExists('telescope_entries');
        Schema::dropIfExists('telescope_monitoring');
    }
};
