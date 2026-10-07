<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('father_contact_number', 32)->nullable()->after('father_native_place');
            $table->string('mother_contact_number', 32)->nullable()->after('mother_native_place');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['father_contact_number', 'mother_contact_number']);
        });
    }
};
