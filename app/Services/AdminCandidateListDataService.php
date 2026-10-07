<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Batch-loads admin candidate list rows without per-user detail queries.
 */
class AdminCandidateListDataService
{
    public function __construct(private readonly CandidateCardDataService $cardData) {}

    /**
     * @param  Collection<int, User>  $users
     *
     * @return list<array{
     *     user: User,
     *     profileImageUrl: string,
     *     profileIconUrl: ?string,
     *     identityVerified: bool,
     *     profileCompletenessPercent: int
     * }>
     */
    public function buildListPayloads(Collection $users): array
    {
        if ($users->isEmpty()) {
            return [];
        }

        $ids = array_values($users->pluck('id')->map(static fn($id): int => (int) $id)->all());
        $photoMap = $this->cardData->profileImageUrlByUserId($ids);
        $identityMap = $this->cardData->identityVerifiedByUserId($ids);
        $out = [];

        foreach ($users as $user) {
            $id = $user->id;
            $out[] = [
                'user' => $user,
                'profileImageUrl' => $photoMap[$id] ?? '',
                'profileIconUrl' => null,
                'identityVerified' => $identityMap[$id] ?? false,
                'profileCompletenessPercent' => self::profileCompletenessPercent($user),
            ];
        }

        return $out;
    }

    public static function currentLocationLine(User $user): string
    {
        $parts = array_values(
            array_filter(
                [
                    data_get($user, 'current_village'),
                    data_get($user, 'current_city'),
                    data_get($user, 'current_state'),
                    data_get($user, 'current_country'),
                ],
                static fn(mixed $part): bool => is_string($part) && trim($part) !== ''
            )
        );

        return implode(', ', $parts);
    }

    public static function profileCompletenessPercent(User $user): int
    {
        $total = count(CandidateProfileSectionService::sections());

        if ($total === 0) {
            return 0;
        }

        $completed = count(
            array_unique(
                array_filter(
                    (array) $user->completed_sections_json,
                    static fn(mixed $section): bool => is_string($section) && $section !== ''
                )
            )
        );

        return (int) round(($completed / $total) * 100);
    }
}
