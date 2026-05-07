<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\CandidateProfileOptionSets;
use Illuminate\Support\Collection;
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
     * Active countries with nested states; each state has cities and districts (DB: both belong to state);
     * each district has villages. Matches FK graph: country→state→city, state→district→village.
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

        $countryIds = $countries->pluck('id')->map(static fn($id): int => (int) $id)->all();
        $states = DB::table('states')
            ->whereIn('country_id', $countryIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'country_id', 'name', 'code']);

        if ($states->isEmpty()) {
            return $countries
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
                ->values()
                ->all();
        }

        $stateIds = $states->pluck('id')->map(static fn($id): int => (int) $id)->unique()->values()->all();

        $cities = DB::table('cities')
            ->whereIn('state_id', $stateIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'state_id', 'name']);

        $districts = DB::table('districts')
            ->whereIn('state_id', $stateIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'state_id', 'name']);

        $districtIds = $districts->pluck('id')->map(static fn($id): int => (int) $id)->unique()->values()->all();

        $villages =
            $districtIds === []
                ? collect()
                : DB::table('villages')
                    ->whereIn('district_id', $districtIds)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'district_id', 'name']);

        $villagesByDistrict = $villages
            ->groupBy(static fn(object $v): int => (int) $v->district_id)
            ->map(static function (Collection $rows): array {
                return $rows
                    ->map(
                        static fn(object $v): array => [
                            'id' => (int) $v->id,
                            'name' => (string) $v->name,
                        ]
                    )
                    ->values()
                    ->all();
            });

        $districtsByState = $districts
            ->groupBy(static fn(object $d): int => (int) $d->state_id)
            ->map(static function (Collection $rows) use ($villagesByDistrict): array {
                return $rows
                    ->map(static function (object $d) use ($villagesByDistrict): array {
                        $did = (int) $d->id;

                        return [
                            'id' => $did,
                            'name' => (string) $d->name,
                            'villages' => collect($villagesByDistrict->get($did, []))->values()->all(),
                        ];
                    })
                    ->values()
                    ->all();
            });

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
            ->map(static function (Collection $rows) use ($citiesByState, $districtsByState): array {
                return $rows
                    ->map(static function (object $s) use ($citiesByState, $districtsByState): array {
                        $sid = (int) $s->id;

                        return [
                            'id' => $sid,
                            'name' => (string) $s->name,
                            'code' => $s->code !== null ? (string) $s->code : null,
                            'cities' => collect($citiesByState->get($sid, []))->values()->all(),
                            'districts' => collect($districtsByState->get($sid, []))->values()->all(),
                        ];
                    })
                    ->values()
                    ->all();
            });

        return $countries
            ->map(static function (object $c) use ($statesByCountry): array {
                $cid = (int) $c->id;

                return [
                    'id' => $cid,
                    'name' => (string) $c->name,
                    'iso2' => $c->iso2 !== null ? (string) $c->iso2 : null,
                    'iso3' => $c->iso3 !== null ? (string) $c->iso3 : null,
                    'phoneCode' => $c->phone_code !== null ? (string) $c->phone_code : null,
                    'states' => collect($statesByCountry->get($cid, []))->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
