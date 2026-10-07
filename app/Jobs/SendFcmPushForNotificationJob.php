<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\FcmPushService;
use App\Services\MemberNotificationFeedService;
use App\Services\UserPushDeviceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmPushForNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $notificationData  Raw notification toArray() payload (snake_case keys)
     */
    public function __construct(public readonly int $userId, public readonly array $notificationData) {}

    public function handle(FcmPushService $fcm, UserPushDeviceService $devices): void
    {
        $kind = $this->notificationData['kind'] ?? null;

        if (!is_string($kind) || !in_array($kind, MemberNotificationFeedService::FEED_KINDS, true)) {
            return;
        }

        $user = User::query()->find($this->userId);

        if (!($user instanceof User)) {
            return;
        }

        $tokens = array_map(static fn($d): string => $d->fcm_token, $devices->activeDevicesForUser($user));

        if ($tokens === []) {
            return;
        }

        $title = $this->titleForKind($kind);
        $body =
            isset($this->notificationData['message']) && is_string($this->notificationData['message'])
                ? $this->notificationData['message']
                : $title;

        $data = [
            'kind' => $kind,
            'deep_link' => $this->deepLinkForKind($kind, $this->notificationData),
        ];

        foreach (
            [
                'user_uuid',
                'from_user_uuid',
                'to_user_uuid',
                'other_user_uuid',
                'viewer_user_uuid',
                'contact_request_uuid',
                'document_uuid',
            ] as $key
        ) {
            if (isset($this->notificationData[$key]) && is_scalar($this->notificationData[$key])) {
                $data[$key] = (string) $this->notificationData[$key];
            }
        }

        $fcm->sendToTokens($tokens, $title, $body, $data);
    }

    private function titleForKind(string $kind): string
    {
        return match ($kind) {
            'contact_request_received' => 'New contact request',
            'contact_request_accepted' => 'Contact request accepted',
            'new_match' => 'New match',
            'profile_viewed' => 'Profile view',
            'kyc_approved' => 'Identity verified',
            'kyc_rejected' => 'Identity verification update',
            'profile_published' => 'Profile published',
            'payment_succeeded' => 'Payment successful',
            'payment_failed' => 'Payment failed',
            default => 'Kurmi Vivah',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function deepLinkForKind(string $kind, array $data): string
    {
        if (isset($data['deep_link']) && is_string($data['deep_link']) && $data['deep_link'] !== '') {
            return $data['deep_link'];
        }

        return match ($kind) {
            'kyc_approved', 'kyc_rejected' => '/account/verify-identity',
            'contact_request_received',
            'contact_request_accepted',
            'new_match',
            'profile_viewed' => '/account/notifications',
            'payment_succeeded', 'payment_failed' => '/account/payment',
            'profile_published' => '/account/my-profile',
            default => '/account/notifications',
        };
    }
}
