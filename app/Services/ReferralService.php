<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    public function ensureReferralCode(User $user): string
    {
        $existing = (string) ($user->referral_code ?? '');

        if ($existing !== '') {
            return $existing;
        }

        // Stable-enough, human-friendly code; uniqueness enforced by DB index.
        $base = strtoupper(trim((string) ($user->last_name ?? 'USER')));
        $base = preg_replace('/[^A-Z0-9]+/', '-', $base) ?? 'USER';
        $base = trim($base, '-');

        if ($base === '') {
            $base = 'USER';
        }

        $code = $base . '-' . strtoupper(substr((string) $user->uuid, 0, 8));

        // Retry a couple times in case of extremely unlikely collisions.
        for ($i = 0; $i < 3; $i++) {
            try {
                $user->forceFill(['referral_code' => $code])->save();
                $user->refresh();

                return (string) $user->referral_code;
            } catch (\Throwable) {
                $code = $base . '-' . strtoupper(Str::random(8));
            }
        }

        // Last resort.
        $code = 'REF-' . strtoupper(Str::random(10));
        $user->forceFill(['referral_code' => $code])->save();
        $user->refresh();

        return (string) $user->referral_code;
    }

    /**
     * @return array{
     *   code:string,
     *   shareUrl:?string,
     *   rewardSummary:array{successfulReferrals:int,rewardMonthsEarned:int},
     *   entries:list<array{id:string,name:string,invitedAt:string,status:string}>
     * }
     */
    public function referralPayloadForUser(User $user): array
    {
        $code = $this->ensureReferralCode($user);

        $entries = DB::table('referral_entries')
            ->where('inviter_user_id', $user->id)
            ->orderByDesc('invited_at')
            ->orderByDesc('id')
            ->get(['uuid', 'invitee_name', 'invited_at', 'status'])
            ->map(static function (object $row): array {
                $invitedAt = data_get($row, 'invited_at');

                return [
                    'id' => (string) data_get($row, 'uuid'),
                    'name' => (string) data_get($row, 'invitee_name', ''),
                    'invitedAt' => $invitedAt !== null ? (string) $invitedAt : '',
                    'status' => (string) data_get($row, 'status', 'invited'),
                ];
            })
            ->values()
            ->all();

        $successful = 0;

        foreach ($entries as $e) {
            if ($e['status'] === 'rewardEligible') {
                $successful++;
            }
        }

        return [
            'code' => $code,
            'shareUrl' => config('app.url') ? rtrim((string) config('app.url'), '/') . '/r/' . $code : null,
            'rewardSummary' => [
                'successfulReferrals' => $successful,
                'rewardMonthsEarned' => $successful,
            ],
            'entries' => $entries,
        ];
    }
}
