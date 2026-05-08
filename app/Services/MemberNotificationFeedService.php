<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContactRequest;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class MemberNotificationFeedService
{
    public function __construct(private readonly CandidateCardDataService $cardData) {}

    /** @var list<string> */
    public const FEED_KINDS = [
        'contact_request_received',
        'contact_request_accepted',
        'new_match',
        'profile_viewed',
        'payment_succeeded',
        'payment_failed',
        'profile_published',
    ];

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForUser(User $user, int $perPage, int $page, bool $unreadOnly): LengthAwarePaginator
    {
        $query = $user->notifications()->whereIn('data->kind', self::FEED_KINDS)->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        /** @var LengthAwarePaginator<int, DatabaseNotification> $paginator */
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $notifications = collect($paginator->items());
        $imageUrlByRelatedUuid = $this->profileImageUrlByRelatedUserUuid($notifications);
        $contactMetaByUuid = $this->contactRequestMetaByUuid($notifications);
        $mappedItems = $notifications
            ->map(
                fn(DatabaseNotification $n): array => $this->toFeedItem($n, $imageUrlByRelatedUuid, $contactMetaByUuid)
            )
            ->values()
            ->all();

        return new LengthAwarePaginator($mappedItems, $paginator->total(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    public function unreadCountForUser(User $user): int
    {
        return $user->notifications()->whereNull('read_at')->whereIn('data->kind', self::FEED_KINDS)->count();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findFeedItemForUser(User $user, string $notificationId): ?array
    {
        /** @var DatabaseNotification|null $n */
        $n = $user->notifications()->whereKey($notificationId)->first();
        if (!$n instanceof DatabaseNotification) {
            return null;
        }
        $kind = data_get($n->data, 'kind');
        if (!is_string($kind) || !in_array($kind, self::FEED_KINDS, true)) {
            return null;
        }

        $contactMetaByUuid = $this->contactRequestMetaByUuid([$n]);

        return $this->toFeedItem($n, [], $contactMetaByUuid);
    }

    /**
     * @param  array<string, string>  $profileImageUrlByRelatedUuid  Related user's `users.uuid` => absolute image URL (from batch); empty loads per row when needed
     * @param  array<string, array{status: string, updatedAt: ?string}>  $contactRequestMetaByUuid  `contact_requests.uuid` => meta
     * @return array<string, mixed>
     */
    public function toFeedItem(
        DatabaseNotification $n,
        array $profileImageUrlByRelatedUuid = [],
        array $contactRequestMetaByUuid = []
    ): array {
        /** @var mixed $raw */
        $raw = $n->data;
        $data = is_array($raw) ? $raw : [];
        $kind = '';
        if (isset($data['kind']) && is_string($data['kind'])) {
            $kind = $data['kind'];
        }

        $relatedUuid = $this->relatedUserUuid($kind, $data);
        $profileImageUrl = $this->cardData->defaultPhotoUrl();
        if ($relatedUuid !== null && $relatedUuid !== '') {
            $profileImageUrl =
                $profileImageUrlByRelatedUuid[$relatedUuid] ?? $this->profileImageUrlForUserUuid($relatedUuid);
        }

        $contactRequestUuid = $this->contactRequestUuid($kind, $data);
        $contactStatus = null;
        $contactUpdatedAt = null;
        if ($contactRequestUuid !== null && isset($contactRequestMetaByUuid[$contactRequestUuid])) {
            $contactStatus = $contactRequestMetaByUuid[$contactRequestUuid]['status'];
            $contactUpdatedAt = $contactRequestMetaByUuid[$contactRequestUuid]['updatedAt'];
        }

        return [
            'id' => $n->id,
            'kind' => $kind,
            'message' => isset($data['message']) && is_string($data['message']) ? $data['message'] : '',
            'iconKey' => $this->iconKeyForKind($kind),
            'profileImageUrl' => $profileImageUrl,
            'contactRequestStatus' => $contactStatus,
            'contactRequestUpdatedAt' => $contactUpdatedAt,
            'createdAt' => $n->created_at !== null ? $n->created_at->toIso8601String() : null,
            'readAt' => $n->read_at !== null ? $n->read_at->toIso8601String() : null,
            'data' => $this->camelizeKeys($data),
            'actions' => $this->actionsForKind($kind, $data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data  Raw notification JSON (snake_case keys)
     */
    private function relatedUserUuid(string $kind, array $data): ?string
    {
        $uuid = match ($kind) {
            'contact_request_received' => $data['from_user_uuid'] ?? null,
            'contact_request_accepted' => $data['to_user_uuid'] ?? null,
            'new_match' => $data['other_user_uuid'] ?? null,
            'profile_viewed' => $data['viewer_user_uuid'] ?? null,
            'profile_published' => $data['user_uuid'] ?? null,
            default => null,
        };

        return is_string($uuid) && $uuid !== '' ? $uuid : null;
    }

    /**
     * @param  array<string, mixed>  $data  Raw notification JSON (snake_case keys)
     */
    private function contactRequestUuid(string $kind, array $data): ?string
    {
        if (!in_array($kind, ['contact_request_received', 'contact_request_accepted'], true)) {
            return null;
        }

        $uuid = $data['contact_request_uuid'] ?? null;

        return is_string($uuid) && $uuid !== '' ? $uuid : null;
    }

    /**
     * @param  iterable<int, DatabaseNotification>  $notifications
     * @return array<string, array{status: string, updatedAt: ?string}> uuid => meta
     */
    private function contactRequestMetaByUuid(iterable $notifications): array
    {
        $uuidSet = [];
        foreach ($notifications as $n) {
            /** @var mixed $raw */
            $raw = $n->data;
            $data = is_array($raw) ? $raw : [];
            $kind = isset($data['kind']) && is_string($data['kind']) ? $data['kind'] : '';
            $u = $this->contactRequestUuid($kind, $data);
            if ($u !== null) {
                $uuidSet[$u] = true;
            }
        }

        $uuids = array_keys($uuidSet);
        if ($uuids === []) {
            return [];
        }

        $rows = ContactRequest::query()
            ->whereIn('uuid', $uuids)
            ->get(['uuid', 'request_status', 'updated_at']);

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->uuid] = [
                'status' => (string) $row->request_status,
                'updatedAt' => $row->updated_at?->toIso8601String(),
            ];
        }
        foreach ($uuids as $uuid) {
            if (!isset($out[$uuid])) {
                $out[$uuid] = ['status' => 'unknown', 'updatedAt' => null];
            }
        }

        return $out;
    }

    /**
     * @param  iterable<int, DatabaseNotification>  $notifications
     * @return array<string, string> uuid => absolute profile image URL
     */
    private function profileImageUrlByRelatedUserUuid(iterable $notifications): array
    {
        $uuidSet = [];
        foreach ($notifications as $n) {
            /** @var mixed $raw */
            $raw = $n->data;
            $data = is_array($raw) ? $raw : [];
            $kind = isset($data['kind']) && is_string($data['kind']) ? $data['kind'] : '';
            $u = $this->relatedUserUuid($kind, $data);
            if ($u !== null) {
                $uuidSet[$u] = true;
            }
        }
        $uuidList = array_keys($uuidSet);
        if ($uuidList === []) {
            return [];
        }

        /** @var array<string, int|string> $idByUuid */
        $idByUuid = User::query()->whereIn('uuid', $uuidList)->pluck('id', 'uuid')->all();
        $ids = [];
        foreach ($idByUuid as $id) {
            $ids[] = (int) $id;
        }
        $urlByUserId = $this->cardData->profileImageUrlByUserId($ids);

        $out = [];
        foreach ($idByUuid as $uuid => $id) {
            $out[$uuid] = $urlByUserId[(int) $id] ?? $this->cardData->defaultPhotoUrl();
        }
        foreach ($uuidList as $uuid) {
            if (!isset($out[$uuid])) {
                $out[$uuid] = $this->cardData->defaultPhotoUrl();
            }
        }

        return $out;
    }

    private function profileImageUrlForUserUuid(string $uuid): string
    {
        $id = User::query()->where('uuid', $uuid)->value('id');
        if (!is_numeric($id)) {
            return $this->cardData->defaultPhotoUrl();
        }
        $map = $this->cardData->profileImageUrlByUserId([(int) $id]);

        return $map[(int) $id] ?? $this->cardData->defaultPhotoUrl();
    }

    private function iconKeyForKind(string $kind): string
    {
        return match ($kind) {
            'contact_request_received', 'contact_request_accepted' => 'contact_request',
            'new_match' => 'new_match',
            'profile_viewed' => 'profile_viewed',
            'payment_succeeded', 'payment_failed' => 'payment',
            'profile_published' => 'profile_published',
            default => 'default',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function actionsForKind(string $kind, array $data): array
    {
        if ($kind === 'contact_request_received') {
            $uuid =
                isset($data['contact_request_uuid']) && is_string($data['contact_request_uuid'])
                    ? $data['contact_request_uuid']
                    : null;
            if ($uuid === null || $uuid === '') {
                return [];
            }
            $path = '/api/v1/auth/candidate/contact-requests/' . $uuid;

            return [
                [
                    'action' => 'accept_contact_request',
                    'label' => 'Accept',
                    'method' => 'PATCH',
                    'path' => $path,
                    'body' => ['decision' => 'accepted'],
                ],
                [
                    'action' => 'reject_contact_request',
                    'label' => 'Reject',
                    'method' => 'PATCH',
                    'path' => $path,
                    'body' => ['decision' => 'rejected'],
                ],
            ];
        }

        if ($kind === 'new_match') {
            return [
                [
                    'action' => 'open_matches',
                    'label' => 'View matches',
                    'method' => 'GET',
                    'path' => '/api/v1/auth/candidate/matches',
                    'body' => null,
                ],
            ];
        }

        if ($kind === 'profile_viewed') {
            $viewerUuid =
                isset($data['viewer_user_uuid']) && is_string($data['viewer_user_uuid'])
                    ? $data['viewer_user_uuid']
                    : null;
            if ($viewerUuid === null || $viewerUuid === '') {
                return [];
            }

            return [
                [
                    'action' => 'open_profile',
                    'label' => 'View profile',
                    'method' => 'GET',
                    'path' => '/api/v1/admin/candidates/' . $viewerUuid . '/profile-details',
                    'body' => null,
                ],
            ];
        }

        if ($kind === 'contact_request_accepted') {
            $toUuid = isset($data['to_user_uuid']) && is_string($data['to_user_uuid']) ? $data['to_user_uuid'] : null;
            if ($toUuid === null || $toUuid === '') {
                return [];
            }

            return [
                [
                    'action' => 'open_profile',
                    'label' => 'View profile',
                    'method' => 'GET',
                    'path' => '/api/v1/admin/candidates/' . $toUuid . '/profile-details',
                    'body' => null,
                ],
            ];
        }

        if ($kind === 'payment_succeeded' || $kind === 'payment_failed') {
            $payUuid = isset($data['payment_uuid']) && is_string($data['payment_uuid']) ? $data['payment_uuid'] : null;
            if ($payUuid === null || $payUuid === '') {
                return [];
            }

            return [
                [
                    'action' => 'view_registration_payment',
                    'label' => 'Payment status',
                    'method' => 'GET',
                    'path' => '/api/v1/auth/payment/registration/' . $payUuid . '/status',
                    'body' => null,
                ],
            ];
        }

        if ($kind === 'profile_published') {
            return [
                [
                    'action' => 'open_me',
                    'label' => 'View profile',
                    'method' => 'GET',
                    'path' => '/api/v1/auth/me',
                    'body' => null,
                ],
            ];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function camelizeKeys(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $camel = Str::camel((string) $key);
            if (is_array($value) && $this->isAssoc($value)) {
                $out[$camel] = $this->camelizeKeys($value);
            } else {
                $out[$camel] = $value;
            }
        }

        return $out;
    }

    /**
     * @param  array<mixed>  $arr
     */
    private function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
