<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProfileSpamReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProfileSpamReportService
{
    /** @var list<string> */
    public const OPEN_STATUSES = ['pending', 'reviewing', 'action_taken'];

    /**
     * Candidate user IDs this viewer has reported with an open (non-dismissed) report.
     *
     * @return list<int>
     */
    public function reportedUserIdsForViewer(User $viewer): array
    {
        $query = ProfileSpamReport::query()->where('reporter_user_id', $viewer->id);
        $query->getQuery()->whereIn('status', self::OPEN_STATUSES);

        return array_values($query->pluck('reported_user_id')->map(static fn($id): int => (int) $id)->unique()->all());
    }

    /**
     * Candidate UUIDs this viewer has reported with an open report (for Algolia filters).
     *
     * @return list<string>
     */
    public function reportedUserUuidsForViewer(User $viewer): array
    {
        $ids = $this->reportedUserIdsForViewer($viewer);

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

    public function report(User $reporter, User $reported, string $reason): ProfileSpamReport
    {
        if ($reporter->id === $reported->id) {
            throw ValidationException::withMessages([
                'candidate' => ['You cannot report your own profile.'],
            ]);
        }

        if (!$reported->hasRole('candidate')) {
            throw ValidationException::withMessages([
                'candidate' => ['The selected user is not a candidate profile.'],
            ]);
        }

        $pendingExists = ProfileSpamReport::query()
            ->where('reporter_user_id', $reporter->id)
            ->where('reported_user_id', $reported->id)
            ->where('status', 'pending')
            ->toBase()
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'candidate' => ['You have already reported this profile and it is pending review.'],
            ]);
        }

        return ProfileSpamReport::query()->create([
            'reporter_user_id' => $reporter->id,
            'reported_user_id' => $reported->id,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    public function markSpammer(ProfileSpamReport $report, User $admin): ProfileSpamReport
    {
        if ($report->status !== 'pending') {
            throw ValidationException::withMessages([
                'report' => ['This spam report has already been resolved.'],
            ]);
        }

        return DB::transaction(function () use ($report, $admin): ProfileSpamReport {
            $reported = $report->reportedUser;

            if ($reported instanceof User) {
                $reported->profile_status = 'spam';
                $reported->status = 'inactive';
                $reported->save();
            }

            $adminId = $admin->id;

            if ($adminId < 0) {
                throw new \RuntimeException('Invalid admin id.');
            }

            $report->status = 'action_taken';
            $report->reviewed_by = $adminId;
            $report->reviewed_at = now();
            $report->save();

            return $report->fresh(['reporter', 'reportedUser']) ?? $report;
        });
    }

    public function markNotSpammer(ProfileSpamReport $report, User $admin): ProfileSpamReport
    {
        if ($report->status !== 'pending') {
            throw ValidationException::withMessages([
                'report' => ['This spam report has already been resolved.'],
            ]);
        }

        return DB::transaction(function () use ($report, $admin): ProfileSpamReport {
            $adminId = $admin->id;

            if ($adminId < 0) {
                throw new \RuntimeException('Invalid admin id.');
            }

            $report->status = 'dismissed';
            $report->reviewed_by = $adminId;
            $report->reviewed_at = now();
            $report->save();

            $reported = $report->reportedUser;

            if ($reported instanceof User && $reported->profile_status === 'spam') {
                $otherOpen = ProfileSpamReport::query()
                    ->where('reported_user_id', $reported->id)
                    ->where('status', 'pending')
                    ->where('id', '!=', $report->id)
                    ->toBase()
                    ->exists();

                if (!$otherOpen) {
                    $reported->profile_status = $reported->published_at !== null ? 'published' : 'draft';
                    $reported->status = 'active';
                    $reported->save();
                }
            }

            return $report->fresh(['reporter', 'reportedUser']) ?? $report;
        });
    }
}
