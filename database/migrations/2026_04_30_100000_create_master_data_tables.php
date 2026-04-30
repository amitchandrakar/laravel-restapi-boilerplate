<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master data from docs/DATABASE_DESIGN.md (geography + reference lists).
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('iso2', 2)->nullable()->comment('ISO 3166-1 alpha-2 country code.');
                $table->string('iso3', 3)->nullable()->comment('ISO 3166-1 alpha-3 country code.');
                $table->string('phone_code', 16)->nullable()->comment('International dialing prefix, e.g. +91.');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique('iso2');
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('states')) {
            Schema::create('states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 32)->nullable()->comment('State/province code within country.');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['country_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['state_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('districts')) {
            Schema::create('districts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['state_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('villages')) {
            Schema::create('villages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['district_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 32)->nullable()->comment('BCP 47 / internal language code.');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique('code');
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('degrees')) {
            Schema::create('degrees', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('degree_type', 64)->nullable()->comment('e.g. undergraduate, postgraduate.');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('occupations')) {
            Schema::create('occupations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category', 128)->nullable()->comment('High-level occupation grouping.');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('surnames')) {
            Schema::create('surnames', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['language_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surnames');
        Schema::dropIfExists('occupations');
        Schema::dropIfExists('degrees');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};
