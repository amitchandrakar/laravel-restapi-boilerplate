<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class MemberDashboardStatsService
{
    /**
     * @return array{
     *   profileViews:int,
     *   contactRequestsSent:int,
     *   contactRequestsReceived:int,
     *   contactRequestsApproved:int,
     *   contactRequestsDeclined:int,
     *   favorites:int,
     *   matches:int
     * }
     */
    public function statsForUser(User $user): array
    {
        $userId = (int) $user->id;

        $profileViews = (int) DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->distinct('viewer_user_id')
            ->count('viewer_user_id');

        $contactRequestsSent = (int) DB::table('contact_requests')->where('from_user_id', $userId)->count();
        $contactRequestsReceived = (int) DB::table('contact_requests')->where('to_user_id', $userId)->count();
        $contactRequestsApproved = (int) DB::table('contact_requests')
            ->where('to_user_id', $userId)
            ->where('request_status', 'accepted')
            ->count();
        $contactRequestsDeclined = (int) DB::table('contact_requests')
            ->where('to_user_id', $userId)
            ->where('request_status', 'rejected')
            ->count();

        $favorites = (int) DB::table('favorites')->where('user_id', $userId)->whereNull('deleted_at')->count();

        $matches = (int) DB::table('matches')->where('user_id', $userId)->where('match_status', 'active')->count();

        return [
            'profileViews' => max(0, $profileViews),
            'contactRequestsSent' => max(0, $contactRequestsSent),
            'contactRequestsReceived' => max(0, $contactRequestsReceived),
            'contactRequestsApproved' => max(0, $contactRequestsApproved),
            'contactRequestsDeclined' => max(0, $contactRequestsDeclined),
            'favorites' => max(0, $favorites),
            'matches' => max(0, $matches),
        ];
    }
}
