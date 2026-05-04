<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CandidateMatchService
{
    public function __construct(private readonly CandidateCardDataService $cardData) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateMatches(User $viewer, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $base = DB::table('matches as m')
            ->join('users as u', 'u.id', '=', 'm.matched_user_id')
            ->where('m.user_id', $viewer->id)
            ->where('m.match_status', 'active')
            ->whereNull('u.deleted_at')
            ->orderByDesc('m.match_score')
            ->orderByDesc('m.generated_at')
            ->orderByDesc('m.id')
            ->select([
                'm.id as match_row_id',
                'm.uuid as match_uuid',
                'm.match_score',
                'm.match_reason_json',
                'm.matched_user_id',
            ]);

        CandidateDiscoveryFilterApplier::apply($base, $filters, 'u');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $base->paginate($perPage);
        /** @var Collection<int, object> $rows */
        $rows = collect($paginator->items())->values();
        if ($rows->isEmpty()) {
            $paginator->setCollection(collect([]));

            return $paginator;
        }

        $userIds = $rows->pluck('matched_user_id')->map(static fn($id): int => (int) $id)->unique()->values()->all();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');
        $favoriteSet = $this->cardData->favoritedCandidateIdsForViewer((int) $viewer->id, $userIds);
        $premiumMap = $this->cardData->hasPremiumSubscriptionByUserId($userIds);
        $verificationMap = $this->cardData->profileVerificationStatusByUserId($userIds);

        $mergedRows = [];
        foreach ($rows as $row) {
            $uid = (int) $row->matched_user_id;
            $matchedUser = $users->get($uid);
            if (!$matchedUser instanceof User) {
                continue;
            }

            $photoMap = $this->cardData->profileImageUrlByUserId([$uid]);
            $eduMap = $this->cardData->educationSummaryByUserId([$uid]);
            $verification = $verificationMap[$uid] ?? 'not_submitted';
            $reason = $row->match_reason_json;
            if (is_string($reason)) {
                $decoded = json_decode($reason, true);
                $reason = json_last_error() === JSON_ERROR_NONE ? $decoded : $reason;
            }

            $age = $matchedUser->date_of_birth !== null ? $matchedUser->date_of_birth->age : null;

            $mergedRows[] = [
                'matchUuid' => (string) $row->match_uuid,
                'matchPercentage' => $row->match_score !== null ? (int) $row->match_score : null,
                'hasPremiumSubscription' => (bool) ($premiumMap[$uid] ?? false),
                'isVerified' => $verification === 'approved',
                'isFavorite' => isset($favoriteSet[$uid]),
                'matchReason' => $reason,
                'uuid' => $matchedUser->uuid,
                'fullName' => trim($matchedUser->first_name . ' ' . $matchedUser->last_name),
                'firstName' => $matchedUser->first_name,
                'lastName' => $matchedUser->last_name,
                'age' => $age,
                'currentCity' => $matchedUser->current_city,
                'currentState' => $matchedUser->current_state,
                'occupation' => $matchedUser->occupation,
                'profileImageUrl' => $photoMap[$uid] ?? $this->cardData->defaultPhotoUrl(),
                'educationSummary' => $eduMap[$uid] ?? '',
                'profileVerificationStatus' => $verification,
            ];
        }

        // @phpstan-ignore-next-line argument.unresolvableType
        $payload = new Collection($mergedRows);
        $paginator->setCollection($payload->values());

        return $paginator;
    }
}
