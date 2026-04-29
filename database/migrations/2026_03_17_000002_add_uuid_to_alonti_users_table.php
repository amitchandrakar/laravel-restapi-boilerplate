<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('alonti_users', 'uuid')) {
            return;
        }

        Schema::table('alonti_users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('alonti_users', 'uuid')) {
            return;
        }

        Schema::table('alonti_users', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};

