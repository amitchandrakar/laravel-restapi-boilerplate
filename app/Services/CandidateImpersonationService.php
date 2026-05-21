<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\SanctumAuthToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Issues short-lived Sanctum tokens so admins can use the member app as a candidate.
 */
class CandidateImpersonationService
{
    /**
     * @return array{user: User, token: string, token_type: string, expires_at: string}
     */
    public function impersonate(User $candidate, User $admin): array
    {
        $this->assertImpersonationTarget($candidate, $admin);

        $ttlMinutes = max(1, (int) config('api.auth.impersonation_token_ttl_minutes', 60));
        $expiresAt = Carbon::now()->addMinutes($ttlMinutes);
        $tokenName = 'admin-impersonation-' . $admin->id;
        $token = SanctumAuthToken::issueWithExpiry($candidate, $tokenName, $expiresAt);

        Log::warning('admin.candidates.impersonate', [
            'admin_id' => $admin->id,
            'candidate_id' => $candidate->id,
            'candidate_uuid' => $candidate->uuid,
        ]);

        return [
            'user' => $candidate,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    private function assertImpersonationTarget(User $candidate, User $admin): void
    {
        if ($candidate->trashed()) {
            throw ValidationException::withMessages([
                'candidate' => ['Cannot impersonate a deleted candidate.'],
            ]);
        }

        if (!$candidate->hasRole('candidate')) {
            throw ValidationException::withMessages([
                'candidate' => ['User is not a candidate.'],
            ]);
        }

        if ($candidate->id === $admin->id) {
            throw ValidationException::withMessages([
                'candidate' => ['Cannot impersonate yourself.'],
            ]);
        }
    }
}
