<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProfileDoNotShow;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ProfileHideService
{
    /**
     * Candidate user IDs this viewer has muted ("Don't show again").
     *
     * @return list<int>
     */
    public function hiddenUserIdsForViewer(User $viewer): array
    {
        return array_values(
            ProfileDoNotShow::query()
                ->where('user_id', $viewer->id)
                ->pluck('hidden_user_id')
                ->map(static fn($id): int => (int) $id)
                ->unique()
                ->all()
        );
    }

    /**
     * Candidate UUIDs this viewer has muted (for Algolia filters).
     *
     * @return list<string>
     */
    public function hiddenUserUuidsForViewer(User $viewer): array
    {
        $ids = $this->hiddenUserIdsForViewer($viewer);

        if ($ids === []) {
            return [];
        }

        $query = User::query();
        $query->getQuery()->whereIn('id', $ids);

        return array_values(
            $query
                ->pluck('uuid')
                ->map(static fn($uuid): string => (string) $uuid)
                ->filter(static fn(string $uuid): bool => $uuid !== '')
                ->all()
        );
    }

    public function hide(User $viewer, User $hidden, ?string $reason = null): ProfileDoNotShow
    {
        if ($viewer->id === $hidden->id) {
            throw ValidationException::withMessages([
                'candidate' => ['You cannot hide your own profile.'],
            ]);
        }

        if (!$hidden->hasRole('candidate', 'web')) {
            throw ValidationException::withMessages([
                'candidate' => ['The selected user is not a candidate profile.'],
            ]);
        }

        $existing = ProfileDoNotShow::query()
            ->where('user_id', $viewer->id)
            ->where('hidden_user_id', $hidden->id)
            ->first();

        if ($existing instanceof ProfileDoNotShow) {
            return $existing;
        }

        return ProfileDoNotShow::query()->create([
            'user_id' => $viewer->id,
            'hidden_user_id' => $hidden->id,
            'reason' => $reason,
        ]);
    }

    public function unhide(ProfileDoNotShow $row): void
    {
        $row->delete();
    }

    public function unhideForViewer(User $viewer, User $hidden): void
    {
        ProfileDoNotShow::query()->where('user_id', $viewer->id)->where('hidden_user_id', $hidden->id)->delete();
    }
}
