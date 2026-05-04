<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class CandidateDiscoveryFilterApplier
{
    /**
     * Apply discovery list filters to a query whose primary user table is $userTable (e.g. `users` or join alias `u`).
     *
     * @param array{
     *     gender?: string|null,
     *     min_age?: int|null,
     *     max_age?: int|null,
     *     community?: array<int, int>|null,
     *     city?: string|null,
     *     city_id?: int|null,
     *     education?: array<int, int>|null,
     *     occupation?: array<int, int>|null,
     * } $filters
     */
    public static function apply(EloquentBuilder|QueryBuilder $query, array $filters, string $userTable = 'users'): void
    {
        if (($filters['gender'] ?? null) !== null && $filters['gender'] !== '') {
            $query->where("{$userTable}.gender", $filters['gender']);
        }

        $minAge = $filters['min_age'] ?? null;
        $maxAge = $filters['max_age'] ?? null;
        if ($minAge !== null || $maxAge !== null) {
            $query->whereNotNull("{$userTable}.date_of_birth");
        }
        if ($minAge !== null) {
            $query->where("{$userTable}.date_of_birth", '<=', Carbon::now()->subYears((int) $minAge)->toDateString());
        }
        if ($maxAge !== null) {
            $query->where("{$userTable}.date_of_birth", '>=', Carbon::now()->subYears((int) $maxAge)->toDateString());
        }

        $communityIds = $filters['community'] ?? null;
        if (is_array($communityIds) && $communityIds !== []) {
            $communityIds = array_values(array_unique(array_map('intval', $communityIds)));
            $names = DB::table('surnames')->whereIn('id', $communityIds)->pluck('name');
            $normalized = $names
                ->map(static fn(string $n): string => mb_strtolower(trim($n)))
                ->filter()
                ->values()
                ->all();
            if ($normalized !== []) {
                $query->whereIn(DB::raw("LOWER(TRIM({$userTable}.last_name))"), $normalized);
            }
        }

        $cityName = $filters['city'] ?? null;
        if (($filters['city_id'] ?? null) !== null) {
            $resolved = DB::table('cities')->where('id', (int) $filters['city_id'])->value('name');
            if (is_string($resolved) && $resolved !== '') {
                $cityName = $resolved;
            }
        }
        if (is_string($cityName) && trim($cityName) !== '') {
            $query->whereRaw("LOWER(TRIM({$userTable}.current_city)) = ?", [mb_strtolower(trim($cityName))]);
        }

        $educationIds = $filters['education'] ?? null;
        if (is_array($educationIds) && $educationIds !== []) {
            $educationIds = array_values(array_unique(array_map('intval', $educationIds)));
            $query->whereExists(static function ($sub) use ($educationIds, $userTable): void {
                $sub->from('user_education_details as ued')
                    ->whereColumn('ued.user_id', "{$userTable}.id")
                    ->whereIn('ued.degree_id', $educationIds)
                    ->whereNull('ued.deleted_at');
            });
        }

        $occupationIds = $filters['occupation'] ?? null;
        if (is_array($occupationIds) && $occupationIds !== []) {
            $occupationIds = array_values(array_unique(array_map('intval', $occupationIds)));
            $names = DB::table('occupations')->whereIn('id', $occupationIds)->pluck('name');
            $normalized = $names
                ->map(static fn(string $n): string => mb_strtolower(trim($n)))
                ->filter()
                ->values()
                ->all();
            if ($normalized !== []) {
                $query->whereIn(DB::raw("LOWER(TRIM({$userTable}.occupation))"), $normalized);
            }
        }
    }
}
