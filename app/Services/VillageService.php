<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\CacheKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VillageService
{
    /**
     * Find an active village by city + name, or create one (case-insensitive match).
     *
     * @return array{id: int, name: string, cityId: int}
     */
    public function findOrCreate(int $cityId, string $name): array
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'name' => ['Village name is required.'],
            ]);
        }

        $cityExists = DB::table('cities')->where('id', $cityId)->where('is_active', true)->exists();

        if (!$cityExists) {
            throw ValidationException::withMessages([
                'city_id' => ['Selected city is invalid.'],
            ]);
        }

        $existing = DB::table('villages')
            ->where('city_id', $cityId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])
            ->where('is_active', true)
            ->first(['id', 'name', 'city_id']);

        if ($existing !== null) {
            return [
                'id' => (int) $existing->id,
                'name' => (string) $existing->name,
                'cityId' => (int) $existing->city_id,
            ];
        }

        $id = DB::table('villages')->insertGetId([
            'city_id' => $cityId,
            'name' => $trimmed,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::forget(CacheKeys::candidateProfileOptions());

        return [
            'id' => $id,
            'name' => $trimmed,
            'cityId' => $cityId,
        ];
    }
}
