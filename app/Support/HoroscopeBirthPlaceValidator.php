<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class HoroscopeBirthPlaceValidator
{
    /**
     * Validation rules for horoscope payload keys.
     *
     * @param  string|null  $dotPrefix  e.g. `horoscope` for nested admin full-profile payloads (becomes `horoscope.date_of_birth`, …).
     * @return array<string, array<int, mixed|string|\Illuminate\Contracts\Validation\Rule>>
     */
    public static function rules(?string $dotPrefix = null): array
    {
        $p = $dotPrefix === null || $dotPrefix === '' ? '' : rtrim($dotPrefix, '.') . '.';

        return array_merge(
            [
                $p . 'date_of_birth' => ['nullable', 'date'],
                $p . 'time_of_birth' => ['nullable', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
                $p . 'zodiac_sign' => ['nullable', 'string', 'max:64'],
                $p . 'place_of_birth_line' => ['nullable', 'string', 'max:255'],
            ],
            self::flatGeoIdRules('birth_', $p)
        );
    }

    /**
     * Rules for a set of geo FK columns sharing one prefix (e.g. `birth_`, `maternal_`).
     *
     * @return array<string, array<int, mixed|string|\Illuminate\Contracts\Validation\Rule>>
     */
    public static function flatGeoIdRules(string $fieldPrefix, string $dotPrefix = ''): array
    {
        $p = $dotPrefix;

        return [
            $p . $fieldPrefix . 'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            $p . $fieldPrefix . 'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')],
            $p . $fieldPrefix . 'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')],
            $p . $fieldPrefix . 'district_id' => ['nullable', 'integer', Rule::exists('districts', 'id')],
            $p . $fieldPrefix . 'village_id' => ['nullable', 'integer', Rule::exists('villages', 'id')],
        ];
    }

    /**
     * Ensure geo FKs form a valid chain (state→country, city/district→state, village→district).
     *
     * @param  array<string, mixed>  $input
     */
    public static function validateConsistency(Validator $validator, array $input, string $errorKeyPrefix = ''): void
    {
        self::validateGeoIdConsistency($validator, $input, 'birth_', $errorKeyPrefix);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function validateGeoIdConsistency(
        Validator $validator,
        array $input,
        string $fieldPrefix,
        string $errorKeyPrefix = ''
    ): void {
        $field = static fn(string $suffix): string => $fieldPrefix . $suffix;

        $k = static function (string $suffix) use ($errorKeyPrefix, $fieldPrefix): string {
            $name = $fieldPrefix . $suffix;

            return $errorKeyPrefix !== '' ? "{$errorKeyPrefix}.{$name}" : $name;
        };

        $countryId = self::toNullableInt($input[$field('country_id')] ?? null);
        $stateId = self::toNullableInt($input[$field('state_id')] ?? null);
        $cityId = self::toNullableInt($input[$field('city_id')] ?? null);
        $districtId = self::toNullableInt($input[$field('district_id')] ?? null);
        $villageId = self::toNullableInt($input[$field('village_id')] ?? null);

        if ($stateId !== null && $countryId !== null) {
            $actual = DB::table('states')->where('id', $stateId)->value('country_id');
            if ($actual === null || (int) $actual !== $countryId) {
                $validator
                    ->errors()
                    ->add($k('state_id'), 'The selected state does not belong to the selected country.');
            }
        }

        if ($cityId !== null && $stateId !== null) {
            $actual = DB::table('cities')->where('id', $cityId)->value('state_id');
            if ($actual === null || (int) $actual !== $stateId) {
                $validator->errors()->add($k('city_id'), 'The selected city does not belong to the selected state.');
            }
        }

        if ($districtId !== null && $stateId !== null) {
            $actual = DB::table('districts')->where('id', $districtId)->value('state_id');
            if ($actual === null || (int) $actual !== $stateId) {
                $validator
                    ->errors()
                    ->add($k('district_id'), 'The selected district does not belong to the selected state.');
            }
        }

        if ($villageId !== null && $districtId !== null) {
            $actual = DB::table('villages')->where('id', $villageId)->value('district_id');
            if ($actual === null || (int) $actual !== $districtId) {
                $validator
                    ->errors()
                    ->add($k('village_id'), 'The selected village does not belong to the selected district.');
            }
        }
    }

    private static function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
