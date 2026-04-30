<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 128)->nullable()->after('role_id');
            }
            if (!Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 128)->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'profile_photo_url')) {
                $table->string('profile_photo_url', 2048)->nullable()->after('job_title');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index('role_id');
            $table->index(['status', 'role_id']);
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['role_id']);
            foreach (['profile_photo_url', 'job_title', 'department', 'role_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
