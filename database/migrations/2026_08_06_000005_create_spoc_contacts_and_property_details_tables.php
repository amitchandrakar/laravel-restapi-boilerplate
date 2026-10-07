<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_spoc_contacts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 255)->nullable();
            $table
                ->string('relation', 128)
                ->nullable()
                ->comment('Relation to candidate (Father, Mother, Guardian, etc.).');
            $table->string('contact_number', 32)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });

        Schema::create('user_property_details', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table
                ->string('property_type', 64)
                ->nullable()
                ->comment('petrol_pump|office|shop|agricultural_field|house|land|other');
            $table->unsignedInteger('area_sq_ft')->nullable();
            $table->string('city', 128)->nullable();
            $table->string('state', 128)->nullable();
            $table->string('country', 128)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_property_details');
        Schema::dropIfExists('user_spoc_contacts');
    }
};
