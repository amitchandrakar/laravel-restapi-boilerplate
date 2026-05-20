<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\ProfileViewedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileViewService
{
    /**
     * Record a profile view and optionally notify the profile owner (candidate peers only).
     * Notifications are deduplicated per viewer/viewed pair for 24 hours.
     */
    public function recordCandidatePeerView(User $viewer, User $viewed): void
    {
        if (!$viewer->hasRole('candidate') || !$viewed->hasRole('candidate')) {
            return;
        }

        if ((int) $viewer->id === (int) $viewed->id) {
            return;
        }

        $now = now();
        DB::table('profile_views')->insert([
            'uuid' => (string) Str::uuid(),
            'viewer_user_id' => $viewer->id,
            'viewed_user_id' => $viewed->id,
            'source' => 'profile_details',
            'viewed_at' => $now,
            'device_type' => null,
            'created_at' => $now,
        ]);

        $cacheKey = 'profile_viewed_notify:' . $viewed->id . ':' . $viewer->id;

        if (!Cache::add($cacheKey, 1, $now->copy()->addHours(24))) {
            return;
        }

        $viewed->notify(new ProfileViewedNotification($viewer, 'profile_details'));
    }
}
