<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContactRequest;
use App\Models\Favorite;
use App\Models\ProfileDoNotShow;
use App\Models\ProfileSpamReport;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminModerationReportService
{
    public function __construct(private readonly CandidateCardDataService $cardData) {}

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateSpamReports(array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $query = ProfileSpamReport::query()->with(['reporter', 'reportedUser']);
        $query->getQuery()->orderBy('created_at', 'desc');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('reason', 'like', '%' . $search . '%')
                    ->orWhereHas('reporter', static function ($uq) use ($search): void {
                        $uq->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('reportedUser', static function ($uq) use ($search): void {
                        $uq->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        $paginator = $query->paginate($perPage);

        return $this->withMappedRows($paginator, $this->mapSpamRows($paginator->getCollection()));
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateFavoriteReports(array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));
        $search = trim((string) ($filters['search'] ?? ''));

        $query = Favorite::query()
            ->where('deleted_at', null)
            ->with(['user', 'favoriteUser']);
        $query->getQuery()->orderBy('created_at', 'desc');

        if ($search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->whereHas('user', static function ($uq) use ($search): void {
                    $uq->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                })->orWhereHas('favoriteUser', static function ($uq) use ($search): void {
                    $uq->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            });
        }

        $paginator = $query->paginate($perPage);

        return $this->withMappedRows($paginator, $this->mapFavoriteRows($paginator->getCollection()));
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateContactRequestReports(array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $query = ContactRequest::query()->with(['fromUser', 'toUser']);
        $query->getQuery()->orderBy('created_at', 'desc');

        if ($status !== '') {
            $query->where('request_status', $status);
        }

        if ($search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->whereHas('fromUser', static function ($uq) use ($search): void {
                    $uq->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                })->orWhereHas('toUser', static function ($uq) use ($search): void {
                    $uq->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            });
        }

        $paginator = $query->paginate($perPage);

        return $this->withMappedRows($paginator, $this->mapContactRequestRows($paginator->getCollection()));
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateDontShowAgainReports(array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));
        $search = trim((string) ($filters['search'] ?? ''));

        $query = ProfileDoNotShow::query()->with(['viewer', 'hiddenUser']);
        $query->getQuery()->orderBy('created_at', 'desc');

        if ($search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('reason', 'like', '%' . $search . '%')
                    ->orWhereHas('viewer', static function ($uq) use ($search): void {
                        $uq->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('hiddenUser', static function ($uq) use ($search): void {
                        $uq->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        $paginator = $query->paginate($perPage);

        return $this->withMappedRows($paginator, $this->mapDontShowAgainRows($paginator->getCollection()));
    }

    public function unmarkFavorite(Favorite $favorite): Favorite
    {
        if ($favorite->trashed()) {
            throw ValidationException::withMessages([
                'favorite' => ['This favorite has already been removed.'],
            ]);
        }

        $favorite->delete();

        return $favorite->fresh() ?? $favorite;
    }

    public function unmarkDontShowAgain(ProfileDoNotShow $row): void
    {
        $row->delete();
    }

    public function markContacted(ContactRequest $request, User $admin): ContactRequest
    {
        return $this->resolveContactRequest($request, $admin, 'contacted');
    }

    public function markNotContacted(ContactRequest $request, User $admin): ContactRequest
    {
        return $this->resolveContactRequest($request, $admin, 'not_contacted');
    }

    private function resolveContactRequest(ContactRequest $request, User $admin, string $resolution): ContactRequest
    {
        return DB::transaction(function () use ($request, $admin, $resolution): ContactRequest {
            $adminId = $admin->id;

            if ($adminId < 0) {
                throw new \RuntimeException('Invalid admin id.');
            }

            $request->admin_resolution = $resolution;
            $request->admin_resolved_at = now();
            $request->admin_resolved_by = $adminId;
            $request->save();

            return $request->fresh(['fromUser', 'toUser']) ?? $request;
        });
    }

    /**
     * @param  Collection<int, ProfileSpamReport>  $reports
     *
     * @return list<array<string, mixed>>
     */
    private function mapSpamRows(Collection $reports): array
    {
        $users = $reports
            ->flatMap(static fn(ProfileSpamReport $r): array => [$r->reporter, $r->reportedUser])
            ->filter(static fn($u): bool => $u instanceof User)
            ->unique(static fn(User $u): int => $u->id)
            ->values();

        $cardMap = $this->cardMapForUsers($users);

        $out = [];

        foreach ($reports as $report) {
            $reporter = $report->reporter;
            $reported = $report->reportedUser;

            $out[] = [
                'uuid' => $report->uuid,
                'status' => $report->status,
                'reason' => $report->reason,
                'reportedAt' => $report->created_at?->toIso8601String(),
                'reviewedAt' => $report->reviewed_at?->toIso8601String(),
                'reportedBy' => $reporter instanceof User ? $cardMap[$reporter->id] ?? null : null,
                'reportedPerson' => $reported instanceof User ? $cardMap[$reported->id] ?? null : null,
            ];
        }

        return $out;
    }

    /**
     * @param  Collection<int, Favorite>  $favorites
     *
     * @return list<array<string, mixed>>
     */
    private function mapFavoriteRows(Collection $favorites): array
    {
        $users = $favorites
            ->flatMap(static fn(Favorite $f): array => [$f->user, $f->favoriteUser])
            ->filter(static fn($u): bool => $u instanceof User)
            ->unique(static fn(User $u): int => $u->id)
            ->values();

        $cardMap = $this->cardMapForUsers($users);
        $out = [];

        foreach ($favorites as $favorite) {
            $marker = $favorite->user;
            $marked = $favorite->favoriteUser;

            $out[] = [
                'uuid' => $favorite->uuid,
                'markedAt' => $favorite->created_at->toIso8601String(),
                'source' => $favorite->source,
                'markedBy' => $marker instanceof User ? $cardMap[$marker->id] ?? null : null,
                'markedPerson' => $marked instanceof User ? $cardMap[$marked->id] ?? null : null,
            ];
        }

        return $out;
    }

    /**
     * @param  Collection<int, ContactRequest>  $requests
     *
     * @return list<array<string, mixed>>
     */
    private function mapContactRequestRows(Collection $requests): array
    {
        $users = $requests
            ->flatMap(static fn(ContactRequest $r): array => [$r->fromUser, $r->toUser])
            ->filter(static fn($u): bool => $u instanceof User)
            ->unique(static fn(User $u): int => $u->id)
            ->values();

        $cardMap = $this->cardMapForUsers($users);
        $out = [];

        foreach ($requests as $request) {
            $from = $request->fromUser;
            $to = $request->toUser;

            $out[] = [
                'uuid' => $request->uuid,
                'requestStatus' => $request->request_status,
                'adminResolution' => $request->admin_resolution,
                'requestedAt' => $request->created_at?->toIso8601String(),
                'adminResolvedAt' => $request->admin_resolved_at?->toIso8601String(),
                'requestMessage' => $request->request_message,
                'requestedBy' => $from instanceof User ? $cardMap[$from->id] ?? null : null,
                'requestedPerson' => $to instanceof User ? $cardMap[$to->id] ?? null : null,
            ];
        }

        return $out;
    }

    /**
     * @param  Collection<int, ProfileDoNotShow>  $rows
     *
     * @return list<array<string, mixed>>
     */
    private function mapDontShowAgainRows(Collection $rows): array
    {
        $users = $rows
            ->flatMap(static fn(ProfileDoNotShow $r): array => [$r->viewer, $r->hiddenUser])
            ->filter(static fn($u): bool => $u instanceof User)
            ->unique(static fn(User $u): int => $u->id)
            ->values();

        $cardMap = $this->cardMapForUsers($users);
        $out = [];

        foreach ($rows as $row) {
            $marker = $row->viewer;
            $marked = $row->hiddenUser;

            $out[] = [
                'uuid' => $row->uuid,
                'reason' => $row->reason,
                'markedAt' => $row->created_at->toIso8601String(),
                'markedBy' => $marker instanceof User ? $cardMap[$marker->id] ?? null : null,
                'markedPerson' => $marked instanceof User ? $cardMap[$marked->id] ?? null : null,
            ];
        }

        return $out;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  LengthAwarePaginator<int, TModel>  $paginator
     * @param  list<array<string, mixed>>  $rows
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function withMappedRows(LengthAwarePaginator $paginator, array $rows): LengthAwarePaginator
    {
        return new LengthAwarePaginator($rows, $paginator->total(), $paginator->perPage(), $paginator->currentPage(), [
            'path' => $paginator->path(),
            'pageName' => $paginator->getPageName(),
        ]);
    }

    /**
     * @param  Collection<int, User>  $users
     *
     * @return array<int, array<string, mixed>>
     */
    private function cardMapForUsers(Collection $users): array
    {
        if ($users->isEmpty()) {
            return [];
        }

        $payloads = $this->cardData->buildCardPayloads($users, 0, false);
        $map = [];

        foreach ($payloads as $payload) {
            $user = $payload['user'];
            $map[$user->id] = [
                'uuid' => $user->uuid,
                'fullName' => trim($user->first_name . ' ' . $user->last_name),
                'phone' => $user->phone,
                'email' => $user->email,
                'currentCity' => $user->current_city,
                'profileImageUrl' => $payload['profileImageUrl'],
                'educationSummary' => $payload['educationSummary'],
                'profileVerificationStatus' => $payload['profileVerificationStatus'],
            ];
        }

        return $map;
    }
}
