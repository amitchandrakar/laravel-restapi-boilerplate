<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('alonti_users')) {
            return;
        }

        Schema::create('alonti_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('email')->unique();
            $table->string('secondary_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('company')->nullable();
            $table->string('addr')->nullable();
            $table->string('addr2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('password');
            $table->string('forgot_password_link')->nullable();
            $table->boolean('forgot_password_link_valid')->default(false);
            $table->timestamp('creation_date')->nullable();
            $table->timestamp('last_updated')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alonti_users');
    }
};
