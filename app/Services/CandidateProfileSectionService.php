<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
                    'email' => $payload['email'] ?? null,
                    'phone' => $payload['phone'] ?? null,
                    'profile_photo_url' => $payload['photo_url'] ?? null,
                    'religion' => $payload['religion'] ?? null,
                    'caste' => $payload['caste'] ?? null,
                    'sub_caste' => $payload['sub_caste'] ?? null,
                    'community' => $payload['community'] ?? null,
                ]),
                self::SECTION_PHOTOS => $this->savePhotos($user, $payload['photos'] ?? []),
                self::SECTION_PERSONAL_DETAILS => $this->saveUsersData($user, $payload),
                self::SECTION_HOROSCOPE => $this->saveUsersData($user, $payload),
                self::SECTION_LOCATION_FAMILY_ROOTS => $this->saveUsersData($user, $payload),
                self::SECTION_CAREER_EDUCATION => $this->saveCareerEducation($user, $payload),
                self::SECTION_FAMILY_BACKGROUND => $this->saveUsersData($user, $payload),
                self::SECTION_LIFESTYLE => $this->saveUsersData($user, $payload),
                self::SECTION_PARTNER_PREFERENCES => $this->savePartnerPreferences($user, $payload),
                default => throw new InvalidArgumentException('Unsupported section'),
            };

            $completed = collect((array) $user->completed_sections_json)->push($section)->unique()->values()->all();
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
            DB::table('user_education_details')->insert([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $user->id,
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
        $allowed = collect($payload)
            ->filter(static fn($value, $key) => is_string($key) && Schema::hasColumn('user_partner_preferences', $key))
            ->all();
        $allowed['user_id'] = $user->id;
        $allowed['updated_at'] = now();

        DB::table('user_partner_preferences')->updateOrInsert(
            ['user_id' => $user->id],
            array_merge($allowed, [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'created_at' => now(),
            ])
        );
    }
}
