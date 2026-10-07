<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Builds guest-safe teaser profile payloads (no contact, DOB, or gated sections).
 */
class PublicCandidateProfileService
{
    public function __construct(private readonly CandidateCardDataService $cardData) {}

    public function isPubliclyListable(User $candidate): bool
    {
        if (!$candidate->hasRole('candidate')) {
            return false;
        }

        if (($candidate->profile_status ?? '') !== 'published') {
            return false;
        }

        if ($candidate->published_at === null) {
            return false;
        }

        if ($candidate->deleted_at !== null) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildTeaser(User $candidate): array
    {
        $age = $candidate->date_of_birth !== null ? $candidate->date_of_birth->age : null;
        $photoMap = $this->cardData->profileImageUrlByUserId([$candidate->id]);
        $defaultPhoto = config('custom.image.profile_default', '/images/Coming-Soon.png');
        $photoUrl = $photoMap[$candidate->id] ?? (is_string($defaultPhoto) ? $defaultPhoto : '/images/Coming-Soon.png');

        $qualifications = $this->loadQualifications($candidate->id);

        return [
            'uuid' => $candidate->uuid,
            'firstName' => $candidate->first_name,
            'lastName' => $candidate->last_name,
            'age' => $age,
            'phone' => null,
            'profileAccess' => 'public',
            'sections' => [
                'photos' => [
                    [
                        'url' => $photoUrl,
                        'isProfilePhoto' => true,
                    ],
                ],
                'personalDetails' => [
                    'firstName' => $candidate->first_name,
                    'lastName' => $candidate->last_name,
                    'photoUrl' => $photoUrl,
                    'age' => $age,
                ],
                'careerEducation' => [
                    'occupation' => $candidate->occupation,
                    'qualifications' => $qualifications,
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadQualifications(int $userId): array
    {
        $rows = DB::table('user_education_details as ued')
            ->leftJoin('degrees as d', 'd.id', '=', 'ued.degree_id')
            ->where('ued.user_id', $userId)
            ->whereNull('ued.deleted_at')
            ->orderByDesc('ued.is_highest')
            ->orderBy('ued.start_year')
            ->get([
                'ued.id',
                'ued.degree_id',
                'd.name as degree_name',
                'ued.field_of_study',
                'ued.institution_name',
                'ued.education_type',
                'ued.start_year',
                'ued.end_year',
                'ued.grade_or_percentage',
                'ued.is_highest',
            ]);

        return array_values(
            $rows
                ->map(static function (object $row): array {
                    $degId = data_get($row, 'degree_id');
                    $degIdInt = $degId !== null && $degId !== '' ? (int) $degId : null;

                    if ($degIdInt !== null && $degIdInt <= 0) {
                        $degIdInt = null;
                    }

                    return [
                        'id' => (int) data_get($row, 'id'),
                        'degreeId' => $degIdInt,
                        'degreeName' => $row->degree_name !== null ? (string) $row->degree_name : null,
                        'fieldOfStudy' => data_get($row, 'field_of_study'),
                        'institutionName' => data_get($row, 'institution_name'),
                        'educationType' => data_get($row, 'education_type'),
                        'startYear' => data_get($row, 'start_year'),
                        'endYear' => data_get($row, 'end_year'),
                        'gradeOrPercentage' => data_get($row, 'grade_or_percentage'),
                        'isHighest' => (bool) data_get($row, 'is_highest', false),
                    ];
                })
                ->all()
        );
    }
}
