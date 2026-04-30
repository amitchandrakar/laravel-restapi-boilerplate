<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Retire legacy `alonti_users` after `users` is live (see docs/DATABASE_DESIGN.md).
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::dropIfExists('alonti_users');
    }

    public function down(): void
    {
        // Intentionally empty: do not recreate legacy skeleton without data migration.
    }
};
