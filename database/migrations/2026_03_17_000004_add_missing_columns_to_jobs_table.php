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
            if (!Schema::hasColumn('jobs', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('queue');
            }
            if (!Schema::hasColumn('jobs', 'reserved_at')) {
                $table->unsignedInteger('reserved_at')->nullable()->after('attempts');
            }
            if (!Schema::hasColumn('jobs', 'available_at')) {
                $table->unsignedInteger('available_at')->after('reserved_at');
            }
            if (!Schema::hasColumn('jobs', 'created_at')) {
                $table->unsignedInteger('created_at')->after('available_at');
            }
            if (!Schema::hasColumn('jobs', 'payload')) {
                $table->longText('payload')->after('created_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('jobs')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'payload')) {
                $table->dropColumn('payload');
            }
            if (Schema::hasColumn('jobs', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('jobs', 'available_at')) {
                $table->dropColumn('available_at');
            }
            if (Schema::hasColumn('jobs', 'reserved_at')) {
                $table->dropColumn('reserved_at');
            }
            if (Schema::hasColumn('jobs', 'attempts')) {
                $table->dropColumn('attempts');
            }
        });
    }
};
