<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('published_at');
            }
            if (!Schema::hasColumn('users', 'featured_at')) {
                $table->timestamp('featured_at')->nullable()->after('is_featured');
            }
            if (!Schema::hasColumn('users', 'featured_by')) {
                $table->unsignedBigInteger('featured_by')->nullable()->after('featured_at')->comment('users.id');
            }
        });
        if (Schema::hasColumn('users', 'is_featured') && !Schema::hasIndex('users', 'users_featured_list_idx')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index(['is_featured', 'profile_status', 'published_at'], 'users_featured_list_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('users', 'users_featured_list_idx')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex('users_featured_list_idx');
            });
        }
        Schema::table('users', function (Blueprint $table): void {
            foreach (['featured_by', 'featured_at', 'is_featured'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
