<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('jobs')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'queue')) {
                $table->string('queue')->index()->after('id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('jobs') || !Schema::hasColumn('jobs', 'queue')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['queue']);
            $table->dropColumn('queue');
        });
    }
};
