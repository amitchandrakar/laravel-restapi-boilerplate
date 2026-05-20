<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Builds Algolia filter strings for candidate discovery (Scout options).
 */
final class CandidateAlgoliaFilterBuilder
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{filters: string, numericFilters: list<string>}
     */
    public static function build(array $filters, string $excludeUuid): array
    {
        $parts = ['profile_status:published', 'is_searchable:1'];
        $numeric = [];

        if ($excludeUuid !== '') {
            $parts[] = 'NOT uuid:' . self::quoteFilterValue($excludeUuid);
        }

        if (($filters['gender'] ?? null) !== null && $filters['gender'] !== '') {
            $parts[] = 'gender:' . self::quoteFilterValue((string) $filters['gender']);
        }

        $minAge = $filters['min_age'] ?? null;
        $maxAge = $filters['max_age'] ?? null;
        if ($minAge !== null) {
            $numeric[] = 'age>=' . (int) $minAge;
        }
        if ($maxAge !== null) {
            $numeric[] = 'age<=' . (int) $maxAge;
        }

        $communityIds = $filters['community'] ?? null;
        if (is_array($communityIds) && $communityIds !== []) {
            $names = DB::table('surnames')->whereIn('id', array_map('intval', $communityIds))->pluck('name');
            $or = [];
            foreach ($names as $name) {
                $normalized = mb_strtolower(trim((string) $name));
                if ($normalized !== '') {
                    $or[] = 'last_name:' . self::quoteFilterValue($normalized);
                }
            }
            if ($or !== []) {
                $parts[] = '(' . implode(' OR ', $or) . ')';
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
            $parts[] = 'current_city:' . self::quoteFilterValue(mb_strtolower(trim($cityName)));
        }

        $educationIds = $filters['education'] ?? null;
        if (is_array($educationIds) && $educationIds !== []) {
            $or = [];
            foreach (array_unique(array_map('intval', $educationIds)) as $degreeId) {
                $or[] = 'education_degree_ids:' . $degreeId;
            }
            $parts[] = '(' . implode(' OR ', $or) . ')';
        }

        $occupationIds = $filters['occupation'] ?? null;
        if (is_array($occupationIds) && $occupationIds !== []) {
            $names = DB::table('occupations')->whereIn('id', array_map('intval', $occupationIds))->pluck('name');
            $or = [];
            foreach ($names as $name) {
                $normalized = mb_strtolower(trim((string) $name));
                if ($normalized !== '') {
                    $or[] = 'occupation:' . self::quoteFilterValue($normalized);
                }
            }
            if ($or !== []) {
                $parts[] = '(' . implode(' OR ', $or) . ')';
            }
        }

        return [
            'filters' => implode(' AND ', $parts),
            'numericFilters' => $numeric,
        ];
    }

    private static function quoteFilterValue(string $value): string
    {
        if (preg_match('/^[a-zA-Z0-9_\-]+$/', $value) === 1) {
            return $value;
        }

        return '"' . str_replace('"', '\\"', $value) . '"';
    }
}
