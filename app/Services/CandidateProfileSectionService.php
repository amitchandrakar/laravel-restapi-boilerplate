<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CandidateProfileSectionService
{
    public const SECTION_BASICS = 'basics';
    public const SECTION_PHOTOS = 'photos';
    public const SECTION_PERSONAL_DETAILS = 'personal_details';
    public const SECTION_HOROSCOPE = 'horoscope';
    public const SECTION_LOCATION_FAMILY_ROOTS = 'location_family_roots';
    public const SECTION_CAREER_EDUCATION = 'career_education';
    public const SECTION_FAMILY_BACKGROUND = 'family_background';
    public const SECTION_LIFESTYLE = 'lifestyle';
    public const SECTION_PARTNER_PREFERENCES = 'partner_preferences';

    /** @return list<string> */
    public static function sections(): array
    {
        return [
            self::SECTION_BASICS,
            self::SECTION_PHOTOS,
            self::SECTION_PERSONAL_DETAILS,
            self::SECTION_HOROSCOPE,
            self::SECTION_LOCATION_FAMILY_ROOTS,
            self::SECTION_CAREER_EDUCATION,
            self::SECTION_FAMILY_BACKGROUND,
            self::SECTION_LIFESTYLE,
            self::SECTION_PARTNER_PREFERENCES,
        ];
    }

    public function saveSection(User $user, string $section, array $payload): User
    {
        if (!in_array($section, self::sections(), true)) {
            throw new InvalidArgumentException('Unsupported section');
        }

        return DB::transaction(function () use ($user, $section, $payload): User {
            match ($section) {
                self::SECTION_BASICS => $this->saveUsersData($user, [
                    'first_name' => $payload['first_name'] ?? null,
                    'last_name' => $payload['last_name'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'phone' => $payload['phone'] ?? null,
                    'marital_status' => $payload['marital_status'] ?? null,
                    'profile_photo_url' => $payload['photo_url'] ?? null,
                    'religion' => $payload['religion'] ?? null,
                    'caste' => $payload['caste'] ?? null,
                    'sub_caste' => $payload['sub_caste'] ?? null,
                    'community' => $payload['community'] ?? null,
                ]),
                self::SECTION_PHOTOS => $this->savePhotos($user, $payload['photos'] ?? []),
                self::SECTION_PERSONAL_DETAILS => $this->saveUsersData($user, [
                    'first_name' => $payload['first_name'] ?? null,
                    'last_name' => $payload['last_name'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'phone' => $payload['phone'] ?? null,
                    'marital_status' => $payload['marital_status'] ?? null,
                    'gender' => $payload['gender'] ?? null,
                    'body_type' => $payload['body_type'] ?? null,
                    'complexion' => $payload['complexion'] ?? null,
                    'height' => $payload['height'] ?? null,
                    'blood_group' => $payload['blood_group'] ?? null,
                    'manglik_status' => $payload['manglik_status'] ?? null,
                    'about_me' => $payload['about_me'] ?? null,
                    'profile_photo_url' => $payload['photo_url'] ?? null,
                    'religion' => $payload['religion'] ?? null,
                    'caste' => $payload['caste'] ?? null,
                    'sub_caste' => $payload['sub_caste'] ?? null,
                    'community' => $payload['community'] ?? null,
                ]),
                self::SECTION_HOROSCOPE => $this->saveHoroscopeSection($user, $payload),
                self::SECTION_LOCATION_FAMILY_ROOTS => $this->saveLocationFamilyRootsSection($user, $payload),
                self::SECTION_CAREER_EDUCATION => $this->saveCareerEducation($user, $payload),
                self::SECTION_FAMILY_BACKGROUND => $this->saveFamilyBackgroundSection($user, $payload),
                self::SECTION_LIFESTYLE => $this->saveUsersData($user, $payload),
                self::SECTION_PARTNER_PREFERENCES => $this->savePartnerPreferences($user, $payload),
                default => throw new InvalidArgumentException('Unsupported section'),
            };

            $completed = collect((array) $user->completed_sections_json)->push($section)->unique()->values()->all();
            if ($section === self::SECTION_PERSONAL_DETAILS) {
                $completed = collect($completed)->push(self::SECTION_BASICS)->unique()->values()->all();
            }
            $user
                ->forceFill([
                    'profile_status' => 'draft',
                    'completed_sections_json' => $completed,
                ])
                ->save();

            return $user->refresh();
        });
    }

    /** @param array<string, array<string,mixed>> $payload */
    public function saveAllSections(User $user, array $payload): User
    {
        $map = [
            self::SECTION_BASICS => data_get($payload, 'basics', []),
            self::SECTION_PHOTOS => data_get($payload, 'photos', []),
            self::SECTION_PERSONAL_DETAILS => data_get($payload, 'personal_details', []),
            self::SECTION_HOROSCOPE => data_get($payload, 'horoscope', []),
            self::SECTION_LOCATION_FAMILY_ROOTS => data_get($payload, 'location_family_roots', []),
            self::SECTION_CAREER_EDUCATION => data_get($payload, 'career_education', []),
            self::SECTION_FAMILY_BACKGROUND => data_get($payload, 'family_background', []),
            self::SECTION_LIFESTYLE => data_get($payload, 'lifestyle', []),
            self::SECTION_PARTNER_PREFERENCES => data_get($payload, 'partner_preferences', []),
        ];

        foreach ($map as $section => $sectionPayload) {
            if (!is_array($sectionPayload) || $sectionPayload === []) {
                continue;
            }
            $user = $this->saveSection($user, $section, $sectionPayload);
        }

        return $user;
    }

    /** @return array<string,mixed> */
    public function sectionData(User $user, string $section): array
    {
        if (!in_array($section, self::sections(), true)) {
            throw new InvalidArgumentException('Unsupported section');
        }

        return ['section' => $section, 'data' => $user->only([])];
    }

    /** @return array<string,mixed> */
    public function progress(User $user): array
    {
        $completed = collect((array) $user->completed_sections_json)->values()->all();
        $missing = array_values(array_diff(self::sections(), $completed));

        return [
            'profileStatus' => (string) ($user->profile_status ?? 'draft'),
            'completedSections' => $completed,
            'missingSections' => $missing,
            'readyToPublish' => $missing === [],
            'publishedAt' => optional($user->published_at)?->toDateTimeString(),
        ];
    }

    /** @return array<string,mixed> */
    public function publish(User $user): array
    {
        $progress = $this->progress($user);
        if ($progress['readyToPublish'] !== true) {
            return ['published' => false, 'missingSections' => $progress['missingSections']];
        }

        $user
            ->forceFill([
                'profile_status' => 'published',
                'published_at' => now(),
            ])
            ->save();

        return ['published' => true, 'missingSections' => []];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveHoroscopeSection(User $user, array $payload): void
    {
        $allowed = [
            'date_of_birth',
            'time_of_birth',
            'zodiac_sign',
            'place_of_birth_line',
            'birth_country_id',
            'birth_state_id',
            'birth_city_id',
            'birth_district_id',
            'birth_village_id',
        ];
        $columns = [];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = $payload[$key];
            if ($value === null || $value === '') {
                continue;
            }
            if (!Schema::hasColumn('users', $key)) {
                continue;
            }
            if ($key === 'date_of_birth' && $value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }
            $columns[$key] = $value;
        }

        if ($columns !== []) {
            $user->update($columns);
        }
    }

    /**
     * Current / hometown free-text fields plus maternal place FKs on `users`.
     *
     * @param  array<string, mixed>  $payload
     */
    private function saveLocationFamilyRootsSection(User $user, array $payload): void
    {
        $stringKeys = [
            'current_country',
            'current_state',
            'current_city',
            'current_district',
            'current_village',
            'hometown_country',
            'hometown_state',
            'hometown_city',
            'hometown_district',
            'hometown_village',
        ];
        $maternalIdKeys = [
            'maternal_country_id',
            'maternal_state_id',
            'maternal_city_id',
            'maternal_district_id',
            'maternal_village_id',
        ];

        $columns = [];

        foreach ($stringKeys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = $payload[$key];
            if ($value === null || $value === '') {
                continue;
            }
            if (!Schema::hasColumn('users', $key)) {
                continue;
            }
            $columns[$key] = is_string($value) ? $value : (string) $value;
        }

        foreach ($maternalIdKeys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = $payload[$key];
            if ($value === null || $value === '') {
                continue;
            }
            if (!Schema::hasColumn('users', $key)) {
                continue;
            }
            $columns[$key] = (int) $value;
        }

        if ($columns !== []) {
            $user->update($columns);
        }
    }

    private function saveUsersData(User $user, array $payload): void
    {
        $columns = collect($payload)
            ->reject(static fn($value) => $value === null)
            ->filter(static fn($value, $key) => is_string($key) && Schema::hasColumn('users', $key))
            ->all();

        if ($columns !== []) {
            $user->update($columns);
        }
    }

    /** @param list<string> $photos */
    private function savePhotos(User $user, array $photos): void
    {
        DB::table('user_images')->where('user_id', $user->id)->where('image_type', 'profile')->delete();
        foreach (array_slice($photos, 0, 5) as $index => $url) {
            DB::table('user_images')->insert([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $user->id,
                'image_type' => 'profile',
                'image_url' => $url,
                'thumbnail_url' => $url,
                'is_profile_photo' => $index === 0,
                'sort_order' => $index,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Parents / counts on `users`; optional `siblings` replaces `user_siblings_details` rows for this user.
     *
     * @param  array<string, mixed>  $payload
     */
    private function saveFamilyBackgroundSection(User $user, array $payload): void
    {
        $this->saveUsersData($user, $payload);

        if (!array_key_exists('siblings', $payload)) {
            return;
        }
        $siblings = $payload['siblings'];
        if (!is_array($siblings)) {
            return;
        }

        DB::table('user_siblings_details')->where('user_id', $user->id)->delete();
        foreach (array_values(array_slice($siblings, 0, 20)) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $relation = data_get($row, 'relation_type');
            $relationType = $relation === 'sister' ? 'sister' : 'brother';

            DB::table('user_siblings_details')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'name' => (string) data_get($row, 'name', ''),
                'gender' => data_get($row, 'gender'),
                'relation_type' => $relationType,
                'marital_status' => data_get($row, 'marital_status'),
                'occupation' => data_get($row, 'occupation'),
                'education' => data_get($row, 'education'),
                'age' => data_get($row, 'age') !== null && data_get($row, 'age') !== '' ? (int) data_get($row, 'age') : null,
                'is_elder' => (bool) data_get($row, 'is_elder', false),
                'sort_order' => data_get($row, 'sort_order') !== null && data_get($row, 'sort_order') !== ''
                        ? (int) data_get($row, 'sort_order')
                        : $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function saveCareerEducation(User $user, array $payload): void
    {
        $this->saveUsersData($user, [
            'occupation' => $payload['occupation'] ?? null,
            'employer' => $payload['employer'] ?? null,
            'income' => $payload['income'] ?? null,
            'marital_status' => $payload['marital_status'] ?? null,
        ]);

        if (!isset($payload['qualifications']) || !is_array($payload['qualifications'])) {
            return;
        }

        DB::table('user_education_details')->where('user_id', $user->id)->delete();
        foreach ($payload['qualifications'] as $qualification) {
            $degreeId = data_get($qualification, 'degree_id');
            $degreeId = $degreeId !== null && $degreeId !== '' ? (int) $degreeId : null;

            DB::table('user_education_details')->insert([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $user->id,
                'degree_id' => $degreeId,
                'field_of_study' => data_get($qualification, 'field_of_study'),
                'institution_name' => data_get($qualification, 'institution_name'),
                'end_year' => data_get($qualification, 'year_of_graduation'),
                'education_type' => 'graduation',
                'is_highest' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function savePartnerPreferences(User $user, array $payload): void
    {
        $table = 'user_partner_preferences';
        $row = [];

        $scalarColumns = [
            'preferred_gender',
            'preferred_min_age',
            'preferred_max_age',
            'preferred_min_height',
            'preferred_max_height',
            'preferred_marital_status',
            'preferred_diet',
            'preferred_smoking',
            'preferred_drinking',
            'preferred_occupation',
            'preferred_caste',
        ];

        foreach ($scalarColumns as $key) {
            if (!array_key_exists($key, $payload) || !Schema::hasColumn($table, $key)) {
                continue;
            }
            $v = $payload[$key];
            if ($key === 'preferred_min_age' || $key === 'preferred_max_age') {
                $row[$key] = $v === null || $v === '' ? null : (int) $v;

                continue;
            }
            $row[$key] = $v;
        }

        $fkColumns = ['preferred_country_id', 'preferred_state_id', 'preferred_city_id', 'preferred_language_id'];
        foreach ($fkColumns as $key) {
            if (!array_key_exists($key, $payload) || !Schema::hasColumn($table, $key)) {
                continue;
            }
            $v = $payload[$key];
            $row[$key] = $v === null || $v === '' ? null : (int) $v;
        }

        if (array_key_exists('preferred_income_min', $payload) && Schema::hasColumn($table, 'preferred_income_min')) {
            $v = $payload['preferred_income_min'];
            $row['preferred_income_min'] = $v === null || $v === '' ? null : (float) $v;
        }

        if (array_key_exists('preferred_degree_ids', $payload) && Schema::hasColumn($table, 'preferred_degree_ids')) {
            $ids = $payload['preferred_degree_ids'];
            if (is_array($ids)) {
                $row['preferred_degree_ids'] = json_encode(
                    array_values(array_map(static fn($id): int => (int) $id, $ids))
                );
            } elseif ($ids === null) {
                $row['preferred_degree_ids'] = null;
            }
        }

        if (
            array_key_exists('preferred_location_ids', $payload) &&
            Schema::hasColumn($table, 'preferred_location_ids')
        ) {
            $ids = $payload['preferred_location_ids'];
            if (is_array($ids)) {
                $row['preferred_location_ids'] = json_encode(
                    array_values(array_map(static fn($id): int => (int) $id, $ids))
                );
            } elseif ($ids === null) {
                $row['preferred_location_ids'] = null;
            }
        }

        if (
            array_key_exists('preferred_community_ids', $payload) &&
            Schema::hasColumn($table, 'preferred_community_ids')
        ) {
            $ids = $payload['preferred_community_ids'];
            if (is_array($ids)) {
                $row['preferred_community_ids'] = json_encode(
                    array_values(array_map(static fn($id): int => (int) $id, $ids))
                );
            } elseif ($ids === null) {
                $row['preferred_community_ids'] = null;
            }
        }

        if ($row === []) {
            return;
        }

        $now = now();
        $exists = DB::table($table)->where('user_id', $user->id)->exists();

        if ($exists) {
            $row['updated_at'] = $now;
            DB::table($table)->where('user_id', $user->id)->update($row);

            return;
        }

        $row['uuid'] = (string) Str::uuid();
        $row['user_id'] = $user->id;
        $row['created_at'] = $now;
        $row['updated_at'] = $now;
        DB::table($table)->insert($row);
    }
}
