<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'profile_status')) {
                $table->string('profile_status', 20)->default('draft')->after('status');
            }
            if (!Schema::hasColumn('users', 'completed_sections_json')) {
                $table->json('completed_sections_json')->nullable()->after('profile_status');
            }
            if (!Schema::hasColumn('users', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('completed_sections_json');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['published_at', 'completed_sections_json', 'profile_status'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
