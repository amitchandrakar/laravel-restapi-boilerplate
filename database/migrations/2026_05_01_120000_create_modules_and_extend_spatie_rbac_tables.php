<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Admin module catalog + metadata on Spatie {@see Permission}
 * and {@see Role} per docs/module_role_permission.md.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
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
        }

        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('permissions', 'module_id')) {
                $table->foreignId('module_id')->nullable()->after('guard_name')->constrained('modules')->nullOnDelete();
            }
            if (!Schema::hasColumn('permissions', 'action')) {
                $table->string('action', 16)->nullable()->after('module_id')->comment('view|add|edit|delete');
            }
            if (!Schema::hasColumn('permissions', 'title')) {
                $table->string('title')->nullable()->after('action');
            }
            if (!Schema::hasColumn('permissions', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('permissions', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('roles', 'title')) {
                $table->string('title')->nullable()->after('guard_name');
            }
            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('description');
            }
            if (!Schema::hasColumn('roles', 'is_default_registration')) {
                $table->boolean('is_default_registration')->default(false)->after('is_system');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'module_id')) {
                $table->dropForeign(['module_id']);
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            foreach (['uuid', 'module_id', 'action', 'title', 'description', 'is_active'] as $col) {
                if (Schema::hasColumn('permissions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            foreach (['uuid', 'title', 'description', 'is_system', 'is_default_registration'] as $col) {
                if (Schema::hasColumn('roles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('modules');
    }
};
