<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\UserImageStorageUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Batch-loads card/list fields for candidate discovery APIs (photo, education one-liner, verification rollup).
 *
 * Verification rule: no active docs → not_submitted; any rejected → rejected; any pending → pending;
 * otherwise (all approved) → approved.
 */
class CandidateCardDataService
{
    private const PREMIUM_PACKAGE_CODE = 'RISHTA_PRO';

    /**
     * @param  Collection<int, User>  $users
     * @return list<array{user: User, profileImageUrl: string, educationSummary: string, profileVerificationStatus: string, isFavorite?: bool}>
     */
    public function buildCardPayloads(Collection $users, int $viewerId, bool $includeFavoriteFlag): array
    {
        if ($users->isEmpty()) {
            return [];
        }

        $ids = $users->pluck('id')->map(static fn($id): int => (int) $id)->all();
        $photoMap = $this->profileImageUrlByUserId($ids);
        $educationMap = $this->educationSummaryByUserId($ids);
        $verificationMap = $this->profileVerificationStatusByUserId($ids);
        $favoriteMap = $includeFavoriteFlag ? $this->favoritedCandidateIdsForViewer($viewerId, $ids) : [];

        $out = [];
        foreach ($users as $user) {
            $id = (int) $user->id;
            $row = [
                'user' => $user,
                'profileImageUrl' => $photoMap[$id] ?? $this->defaultPhotoUrl(),
                'educationSummary' => $educationMap[$id] ?? '',
                'profileVerificationStatus' => $verificationMap[$id] ?? 'not_submitted',
            ];
            if ($includeFavoriteFlag) {
                $row['isFavorite'] = isset($favoriteMap[$id]);
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, string> user_id => url
     */
    public function profileImageUrlByUserId(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $default = $this->defaultPhotoUrl();
        $rows = DB::table('user_images')
            ->select(['user_id', 'image_url', 'is_profile_photo', 'sort_order'])
            ->whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('user_id')
            ->orderByDesc('is_profile_photo')
            ->orderBy('sort_order')
            ->get();

        $byUser = [];
        foreach ($rows as $row) {
            $uid = (int) $row->user_id;
            if (!isset($byUser[$uid])) {
                $stored = (string) $row->image_url;
                $resolved =
                    UserImageStorageUrl::toAbsoluteHttpUrl(UserImageStorageUrl::publicUrl($stored) ?? $stored) ??
                    $stored;
                $byUser[$uid] = $resolved !== '' ? $resolved : $default;
            }
        }

        $map = [];
        foreach ($userIds as $uid) {
            $map[$uid] = $byUser[$uid] ?? $default;
        }

        return $map;
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, string> user_id => short summary
     */
    public function educationSummaryByUserId(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = DB::table('user_education_details as ued')
            ->leftJoin('degrees as d', 'd.id', '=', 'ued.degree_id')
            ->whereIn('ued.user_id', $userIds)
            ->whereNull('ued.deleted_at')
            ->orderBy('ued.user_id')
            ->orderByDesc('ued.is_highest')
            ->orderByDesc('ued.end_year')
            ->orderByDesc('ued.id')
            ->get(['ued.user_id', 'd.name as degree_name', 'ued.field_of_study', 'ued.institution_name']);

        $map = [];
        foreach ($rows as $row) {
            $uid = (int) $row->user_id;
            if (isset($map[$uid])) {
                continue;
            }
            $parts = array_values(
                array_filter(
                    [
                        $row->degree_name !== null ? (string) $row->degree_name : null,
                        $row->field_of_study !== null ? (string) $row->field_of_study : null,
                        $row->institution_name !== null ? (string) $row->institution_name : null,
                    ],
                    static fn(?string $p): bool => $p !== null && $p !== ''
                )
            );
            $map[$uid] = $parts === [] ? '' : implode(' · ', $parts);
        }

        return $map;
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, string>
     */
    public function profileVerificationStatusByUserId(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = DB::table('user_verification_documents')
            ->select(['user_id', 'verification_status'])
            ->whereIn('user_id', $userIds)
            ->whereNull('deleted_at')
            ->get();

        $byUser = [];
        foreach ($rows as $row) {
            $uid = (int) $row->user_id;
            $byUser[$uid][] = (string) $row->verification_status;
        }

        $map = [];
        foreach ($userIds as $uid) {
            $statuses = $byUser[$uid] ?? [];
            if ($statuses === []) {
                $map[$uid] = 'not_submitted';

                continue;
            }
            if (in_array('rejected', $statuses, true)) {
                $map[$uid] = 'rejected';

                continue;
            }
            if (in_array('pending', $statuses, true)) {
                $map[$uid] = 'pending';

                continue;
            }
            $allApproved = !in_array(
                false,
                array_map(static fn(string $s): bool => $s === 'approved', $statuses),
                true
            );
            $map[$uid] = $allApproved ? 'approved' : 'pending';
        }

        return $map;
    }

    /**
     * @param  list<int>  $candidateIds
     * @return array<int, true>
     */
    public function favoritedCandidateIdsForViewer(int $viewerId, array $candidateIds): array
    {
        if ($candidateIds === []) {
            return [];
        }

        $ids = DB::table('favorites')
            ->where('user_id', $viewerId)
            ->whereIn('favorite_user_id', $candidateIds)
            ->whereNull('deleted_at')
            ->pluck('favorite_user_id')
            ->all();

        $set = [];
        foreach ($ids as $id) {
            $set[(int) $id] = true;
        }

        return $set;
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, bool>
     */
    public function hasPremiumSubscriptionByUserId(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $premiumId = (int) DB::table('packages')->where('code', self::PREMIUM_PACKAGE_CODE)->value('id');
        if ($premiumId === 0) {
            return array_fill_keys($userIds, false);
        }

        $active = DB::table('subscriptions')
            ->whereIn('user_id', $userIds)
            ->where('package_id', $premiumId)
            ->where('subscription_status', 'active')
            ->pluck('user_id')
            ->map(static fn($id): int => (int) $id)
            ->all();

        $set = array_fill_keys($userIds, false);
        foreach ($active as $uid) {
            $set[$uid] = true;
        }

        return $set;
    }

    public function defaultPhotoUrl(): string
    {
        return (string) config('custom.image.profile_default', '/images/Coming-Soon.png');
    }
}
