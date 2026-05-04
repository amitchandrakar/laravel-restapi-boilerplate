<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Squashes prior incremental migrations (marital/about, birth geo, maternal geo,
 * lifestyle column cleanup, partner-preference JSON columns, nullable email) for early dev.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'marital_status')) {
                $table->string('marital_status', 64)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('users', 'manglik_status')) {
                $table->string('manglik_status', 32)->nullable()->after('blood_group');
            }
            if (!Schema::hasColumn('users', 'about_me')) {
                $table->text('about_me')->nullable()->after('manglik_status');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'place_of_birth_line')) {
                $table->string('place_of_birth_line', 255)->nullable()->after('zodiac_sign');
            }
            if (!Schema::hasColumn('users', 'birth_country_id')) {
                $table
                    ->foreignId('birth_country_id')
                    ->nullable()
                    ->after('place_of_birth_village')
                    ->constrained('countries')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'birth_state_id')) {
                $table
                    ->foreignId('birth_state_id')
                    ->nullable()
                    ->after('birth_country_id')
                    ->constrained('states')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'birth_city_id')) {
                $table
                    ->foreignId('birth_city_id')
                    ->nullable()
                    ->after('birth_state_id')
                    ->constrained('cities')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'birth_district_id')) {
                $table
                    ->foreignId('birth_district_id')
                    ->nullable()
                    ->after('birth_city_id')
                    ->constrained('districts')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'birth_village_id')) {
                $table
                    ->foreignId('birth_village_id')
                    ->nullable()
                    ->after('birth_district_id')
                    ->constrained('villages')
                    ->nullOnDelete();
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'maternal_country_id')) {
                $table
                    ->foreignId('maternal_country_id')
                    ->nullable()
                    ->after('hometown_village')
                    ->constrained('countries')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'maternal_state_id')) {
                $table
                    ->foreignId('maternal_state_id')
                    ->nullable()
                    ->after('maternal_country_id')
                    ->constrained('states')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'maternal_city_id')) {
                $table
                    ->foreignId('maternal_city_id')
                    ->nullable()
                    ->after('maternal_state_id')
                    ->constrained('cities')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'maternal_district_id')) {
                $table
                    ->foreignId('maternal_district_id')
                    ->nullable()
                    ->after('maternal_city_id')
                    ->constrained('districts')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'maternal_village_id')) {
                $table
                    ->foreignId('maternal_village_id')
                    ->nullable()
                    ->after('maternal_district_id')
                    ->constrained('villages')
                    ->nullOnDelete();
            }
        });

        Schema::table('user_partner_preferences', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_partner_preferences', 'preferred_degree_ids')) {
                $table->json('preferred_degree_ids')->nullable()->after('preferred_drinking');
            }
            if (!Schema::hasColumn('user_partner_preferences', 'preferred_location_ids')) {
                $table->json('preferred_location_ids')->nullable()->after('preferred_degree_ids');
            }
            if (!Schema::hasColumn('user_partner_preferences', 'preferred_community_ids')) {
                $table->json('preferred_community_ids')->nullable()->after('preferred_location_ids');
            }
            if (!Schema::hasColumn('user_partner_preferences', 'preferred_caste')) {
                $table->string('preferred_caste', 255)->nullable()->after('preferred_community_ids');
            }
        });

        Schema::table('user_partner_preferences', function (Blueprint $table): void {
            if (Schema::hasColumn('user_partner_preferences', 'preferred_education')) {
                $table->dropColumn('preferred_education');
            }
            if (Schema::hasColumn('user_partner_preferences', 'preferred_community')) {
                $table->dropColumn('preferred_community');
            }
        });

        foreach (
            [
                'hobbies',
                'interests',
                'likes',
                'dislikes',
                'preferred_hobbies',
                'preferred_interests',
                'preferred_likes',
                'preferred_dislikes',
                'preferred_education',
                'preferred_community',
            ] as $column
        ) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'email')) {
                return;
            }
            if (!$this->emailColumnIsNullable()) {
                $table->string('email')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'email')) {
                return;
            }
            if ($this->emailColumnIsNullable()) {
                $table->string('email')->nullable(false)->change();
            }
        });

        foreach (
            [
                'maternal_village_id',
                'maternal_district_id',
                'maternal_city_id',
                'maternal_state_id',
                'maternal_country_id',
            ] as $column
        ) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column): void {
                    $table->dropForeign([$column]);
                    $table->dropColumn($column);
                });
            }
        }

        foreach (
            [
                'birth_village_id',
                'birth_district_id',
                'birth_city_id',
                'birth_state_id',
                'birth_country_id',
                'place_of_birth_line',
            ] as $column
        ) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column): void {
                    if (str_ends_with($column, '_id')) {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                });
            }
        }

        Schema::table('users', function (Blueprint $table): void {
            foreach (['about_me', 'manglik_status', 'marital_status'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function emailColumnIsNullable(): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        if ($driver === 'sqlite') {
            $row = $connection->selectOne(
                "select \"notnull\" as email_not_null from pragma_table_info('users') where name = 'email'"
            );

            return $row !== null && (int) $row->email_not_null === 0;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $database = $connection->getDatabaseName();
            $row = $connection->selectOne(
                'select is_nullable from information_schema.columns where table_schema = ? and table_name = ? and column_name = ?',
                [$database, 'users', 'email']
            );
            if ($row === null) {
                return false;
            }
            $rowArr = (array) $row;
            $nullable = $rowArr['is_nullable'] ?? ($rowArr['IS_NULLABLE'] ?? null);

            return $nullable !== null && strtoupper((string) $nullable) === 'YES';
        }

        return false;
    }
};
