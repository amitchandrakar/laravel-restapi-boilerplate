<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contact_requests', function (Blueprint $table): void {
            $table
                ->string('admin_resolution', 32)
                ->nullable()
                ->after('response_message')
                ->comment('contacted|not_contacted');
            $table->timestamp('admin_resolved_at')->nullable()->after('admin_resolution');
            $table
                ->foreignId('admin_resolved_by')
                ->nullable()
                ->after('admin_resolved_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['from_user_id', 'admin_resolution']);
        });
    }

    public function down(): void
    {
        Schema::table('contact_requests', function (Blueprint $table): void {
            $table->dropIndex(['from_user_id', 'admin_resolution']);
            $table->dropConstrainedForeignId('admin_resolved_by');
            $table->dropColumn(['admin_resolution', 'admin_resolved_at']);
        });
    }
};
