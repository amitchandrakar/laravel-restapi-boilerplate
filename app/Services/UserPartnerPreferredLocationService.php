<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserPartnerPreferredLocationService
{
    /**
     * @param  array<int, array{country_id?: int|null, state_id?: int|null, city_id?: int|null}>|array<int, int>|null  $locations
     */
    public function syncForUser(User $user, ?array $locations): void
    {
        DB::table('user_partner_preferred_locations')->where('user_id', $user->id)->delete();

        if ($locations === null || $locations === []) {
            return;
        }

        $now = now();
        $sort = 0;

        foreach ($locations as $entry) {
            if (is_int($entry)) {
                $cityId = $entry;
                $city = DB::table('cities')->where('id', $cityId)->first();

                if ($city === null) {
                    continue;
                }
                $stateId = (int) $city->state_id;
                $state = DB::table('states')->where('id', $stateId)->first();
                $countryId = $state !== null ? (int) $state->country_id : null;
                $row = [
                    'country_id' => $countryId,
                    'state_id' => $stateId,
                    'city_id' => $cityId,
                ];
            } else {
                $row = [
                    'country_id' => isset($entry['country_id']) ? (int) $entry['country_id'] : null,
                    'state_id' => isset($entry['state_id']) ? (int) $entry['state_id'] : null,
                    'city_id' => isset($entry['city_id']) ? (int) $entry['city_id'] : null,
                ];
            }

            if ($row['country_id'] === null && $row['state_id'] === null && $row['city_id'] === null) {
                continue;
            }

            DB::table('user_partner_preferred_locations')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'country_id' => $row['country_id'],
                'state_id' => $row['state_id'],
                'city_id' => $row['city_id'],
                'sort_order' => $sort++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * @return list<array{countryId: ?int, stateId: ?int, cityId: ?int}>
     */
    public function listForUserId(int $userId): array
    {
        return array_values(
            DB::table('user_partner_preferred_locations')
                ->where('user_id', $userId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['country_id', 'state_id', 'city_id'])
                ->map(
                    static fn($row): array => [
                        'countryId' => $row->country_id !== null ? (int) $row->country_id : null,
                        'stateId' => $row->state_id !== null ? (int) $row->state_id : null,
                        'cityId' => $row->city_id !== null ? (int) $row->city_id : null,
                    ]
                )
                ->all()
        );
    }
}
