<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('payments', 'webhook_event_id')) {
                $table->string('webhook_event_id', 64)->nullable()->unique()->after('raw_response_json');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'webhook_event_id')) {
                $table->dropUnique(['webhook_event_id']);
                $table->dropColumn('webhook_event_id');
            }
        });
    }
};
