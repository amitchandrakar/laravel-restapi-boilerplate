<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $stringCols = [
                'sleep_pattern' => 64,
                'working_hours' => 64,
                'social_personality' => 32,
                'dietary_preferences' => 64,
                'drinking_habits' => 32,
                'smoking_habits' => 32,
                'fitness_level' => 64,
                'travel_style' => 64,
                'communication_style' => 64,
                'relationship_with_family' => 64,
                'weekend_preference' => 64,
            ];
            foreach ($stringCols as $col => $len) {
                if (!Schema::hasColumn('users', $col)) {
                    $table->string($col, $len)->nullable();
                }
            }

            foreach (['interests', 'movie_genres', 'hobbies', 'likes', 'dislikes'] as $col) {
                if (!Schema::hasColumn('users', $col)) {
                    $table->json($col)->nullable();
                }
            }
        });

        Schema::table('user_partner_preferences', function (Blueprint $table): void {
            $stringCols = [
                'preferred_sleep_pattern' => 64,
                'preferred_working_hours' => 64,
                'preferred_social_personality' => 32,
                'preferred_dietary_preferences' => 64,
                'preferred_drinking_habits' => 32,
                'preferred_smoking_habits' => 32,
                'preferred_fitness_level' => 64,
                'preferred_travel_style' => 64,
                'preferred_communication_style' => 64,
                'preferred_relationship_with_family' => 64,
                'preferred_weekend_preference' => 64,
            ];
            foreach ($stringCols as $col => $len) {
                if (!Schema::hasColumn('user_partner_preferences', $col)) {
                    $table->string($col, $len)->nullable();
                }
            }

            foreach (
                [
                    'preferred_interests',
                    'preferred_movie_genres',
                    'preferred_hobbies',
                    'preferred_likes',
                    'preferred_dislikes',
                ] as $col
            ) {
                if (!Schema::hasColumn('user_partner_preferences', $col)) {
                    $table->json($col)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_partner_preferences', function (Blueprint $table): void {
            foreach (
                [
                    'preferred_interests',
                    'preferred_movie_genres',
                    'preferred_hobbies',
                    'preferred_likes',
                    'preferred_dislikes',
                ] as $col
            ) {
                if (Schema::hasColumn('user_partner_preferences', $col)) {
                    $table->dropColumn($col);
                }
            }
            foreach (
                [
                    'preferred_sleep_pattern',
                    'preferred_working_hours',
                    'preferred_social_personality',
                    'preferred_dietary_preferences',
                    'preferred_drinking_habits',
                    'preferred_smoking_habits',
                    'preferred_fitness_level',
                    'preferred_travel_style',
                    'preferred_communication_style',
                    'preferred_relationship_with_family',
                    'preferred_weekend_preference',
                ] as $col
            ) {
                if (Schema::hasColumn('user_partner_preferences', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            foreach (['interests', 'movie_genres', 'hobbies', 'likes', 'dislikes'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
            foreach (
                [
                    'sleep_pattern',
                    'working_hours',
                    'social_personality',
                    'dietary_preferences',
                    'drinking_habits',
                    'smoking_habits',
                    'fitness_level',
                    'travel_style',
                    'communication_style',
                    'relationship_with_family',
                    'weekend_preference',
                ] as $col
            ) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
