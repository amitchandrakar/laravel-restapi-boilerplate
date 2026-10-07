<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'place_of_birth_district')) {
                $table
                    ->string('place_of_birth_district', 128)
                    ->nullable()
                    ->after('place_of_birth_city')
                    ->comment('Denormalized birth district label.');
            }

            if (!Schema::hasColumn('users', 'place_of_birth_village')) {
                $table
                    ->string('place_of_birth_village', 128)
                    ->nullable()
                    ->after('place_of_birth_district')
                    ->comment('Denormalized birth village label.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'place_of_birth_village')) {
                $table->dropColumn('place_of_birth_village');
            }

            if (Schema::hasColumn('users', 'place_of_birth_district')) {
                $table->dropColumn('place_of_birth_district');
            }
        });
    }
};
