<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\CacheKeys;
use App\Support\CandidateProfileOptionSets;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CandidateProfileOptionsService
{
    /**
     * All option lists for candidate profile UIs (public, unauthenticated).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $ttl = max(60, (int) config('cache_strategy.profile_options_seconds', 3600));

        return Cache::remember(CacheKeys::candidateProfileOptions(), $ttl, fn (): array => $this->buildAll());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAll(): array
    {
        $surnames = DB::table('surnames')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(
                static fn($row): array => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                ]
            )
            ->values()
            ->all();

        $degrees = DB::table('degrees')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'degree_type'])
            ->map(
                static fn($row): array => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'degreeType' => (string) $row->degree_type,
                ]
            )
            ->values()
            ->all();

        $zodiacSigns = array_map(static function (array $row): array {
            $slug = $row['value'];

            return [
                'value' => $slug,
                'label' => $row['label'],
                'iconUrl' => url('/images/zodiac/' . $slug . '.svg'),
            ];
        }, CandidateProfileOptionSets::zodiacSigns());

        return [
            'surnames' => $surnames,
            'degrees' => $degrees,
            'heights' => CandidateProfileOptionSets::heights(),
            'bodyTypes' => CandidateProfileOptionSets::bodyTypesByGender(),
            'complexions' => CandidateProfileOptionSets::complexions(),
            'bloodGroups' => CandidateProfileOptionSets::bloodGroups(),
            'zodiacSigns' => $zodiacSigns,
            'diets' => CandidateProfileOptionSets::diets(),
            'sleepPatterns' => CandidateProfileOptionSets::sleepPatterns(),
            'workingHours' => CandidateProfileOptionSets::workingHours(),
            'socialPersonalities' => CandidateProfileOptionSets::socialPersonalities(),
            'dietaryPreferences' => CandidateProfileOptionSets::dietaryPreferences(),
            'drinkingHabits' => CandidateProfileOptionSets::drinkingHabits(),
            'smokingHabits' => CandidateProfileOptionSets::smokingHabits(),
            'fitnessLevels' => CandidateProfileOptionSets::fitnessLevels(),
            'travelStyles' => CandidateProfileOptionSets::travelStyles(),
            'communicationStyles' => CandidateProfileOptionSets::communicationStyles(),
            'relationshipsWithFamily' => CandidateProfileOptionSets::relationshipsWithFamily(),
            'weekendPreferences' => CandidateProfileOptionSets::weekendPreferences(),
            'interests' => CandidateProfileOptionSets::interests(),
            'movieGenres' => CandidateProfileOptionSets::movieGenres(),
            'hobbies' => CandidateProfileOptionSets::hobbies(),
            'likes' => CandidateProfileOptionSets::likes(),
            'dislikes' => CandidateProfileOptionSets::dislikes(),
            'countries' => $this->nestedActiveCountries(),
        ];
    }

    /**
     * Active countries with nested states; each state has cities.
     *
     * @return list<array{id: int, name: string, iso2: ?string, iso3: ?string, phoneCode: ?string, states: list<array<string, mixed>>}>
     */
    private function nestedActiveCountries(): array
    {
        $countries = DB::table('countries')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'iso2', 'iso3', 'phone_code']);

        if ($countries->isEmpty()) {
            return [];
        }

        $countryIds = $countries->pluck('id')->map(static fn($id): int => (int) $id)->values()->all();
        $states = DB::table('states')
            ->whereIn('country_id', $countryIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'country_id', 'name', 'code']);

        if ($states->isEmpty()) {
            return array_values(
                $countries
                    ->map(
                        static fn(object $c): array => [
                            'id' => (int) $c->id,
                            'name' => (string) $c->name,
                            'iso2' => $c->iso2 !== null ? (string) $c->iso2 : null,
                            'iso3' => $c->iso3 !== null ? (string) $c->iso3 : null,
                            'phoneCode' => $c->phone_code !== null ? (string) $c->phone_code : null,
                            'states' => [],
                        ]
                    )
                    ->all()
            );
        }

        $stateIds = $states->pluck('id')->map(static fn($id): int => (int) $id)->unique()->values()->all();

        $cities = DB::table('cities')
            ->whereIn('state_id', $stateIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'state_id', 'name']);

        $citiesByState = $cities
            ->groupBy(static fn(object $c): int => (int) $c->state_id)
            ->map(static function (Collection $rows): array {
                return $rows
                    ->map(
                        static fn(object $c): array => [
                            'id' => (int) $c->id,
                            'name' => (string) $c->name,
                        ]
                    )
                    ->values()
                    ->all();
            });

        $statesByCountry = $states
            ->groupBy(static fn(object $s): int => (int) $s->country_id)
            ->map(static function (Collection $rows) use ($citiesByState): array {
                return $rows
                    ->map(static function (object $s) use ($citiesByState): array {
                        $sid = (int) $s->id;

                        return [
                            'id' => $sid,
                            'name' => (string) $s->name,
                            'code' => $s->code !== null ? (string) $s->code : null,
                            'cities' => array_values($citiesByState->get($sid, [])),
                        ];
                    })
                    ->values()
                    ->all();
            });

        return array_values(
            $countries
                ->map(static function (object $c) use ($statesByCountry): array {
                    $cid = (int) $c->id;

                    return [
                        'id' => $cid,
                        'name' => (string) $c->name,
                        'iso2' => $c->iso2 !== null ? (string) $c->iso2 : null,
                        'iso3' => $c->iso3 !== null ? (string) $c->iso3 : null,
                        'phoneCode' => $c->phone_code !== null ? (string) $c->phone_code : null,
                        'states' => array_values($statesByCountry->get($cid, [])),
                    ];
                })
                ->all()
        );
    }
}
