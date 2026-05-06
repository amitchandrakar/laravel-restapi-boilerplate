<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ContactRequest;
use App\Models\User;
use App\Notifications\ContactRequestAcceptedNotification;
use App\Notifications\ContactRequestReceivedNotification;
use App\Notifications\NewMatchNotification;
use App\Notifications\ProfileViewedNotification;
use App\Services\MemberNotificationFeedService;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Sample in-app notifications for demo candidates (after {@see DemoUsersSeeder}).
 * Idempotent: removes prior seed contact rows and feed notifications for the same demo users.
 */
class DemoCandidateNotificationsSeeder extends Seeder
{
    private const PENDING_MESSAGE = 'Demo seed: pending contact request.';

    private const ACCEPTED_MESSAGE = 'Demo seed: accepted contact request sample.';

    public function run(): void
    {
        $arjun = User::query()->where('email', 'arjun.mehta@demo.alonti.local')->first();
        $priya = User::query()->where('email', 'priya.shah@demo.alonti.local')->first();
        $rohan = User::query()->where('email', 'rohan.kulkarni@demo.alonti.local')->first();
        $ananya = User::query()->where('email', 'ananya.desai@demo.alonti.local')->first();

        if (!$arjun instanceof User || !$priya instanceof User || !$rohan instanceof User || !$ananya instanceof User) {
            return;
        }

        $demoUserIds = [$arjun->id, $priya->id, $rohan->id, $ananya->id];

        $this->deleteSeedContactRequests($priya, $arjun, $rohan);
        $this->deleteFeedNotificationsForUsers($demoUserIds);

        $pending = ContactRequest::query()->create([
            'from_user_id' => $priya->id,
            'to_user_id' => $arjun->id,
            'request_message' => self::PENDING_MESSAGE,
            'request_status' => 'pending',
        ]);
        $arjun->notify(new ContactRequestReceivedNotification($pending));

        $accepted = ContactRequest::query()->create([
            'from_user_id' => $rohan->id,
            'to_user_id' => $arjun->id,
            'request_message' => self::ACCEPTED_MESSAGE,
            'request_status' => 'accepted',
            'responded_at' => now(),
        ]);
        $rohan->notify(new ContactRequestAcceptedNotification($accepted));

        $arjun->notify(new NewMatchNotification($priya, (string) Str::uuid(), 88));
        $arjun->notify(new ProfileViewedNotification($priya, 'profile_details'));

        $priya->notify(new NewMatchNotification($ananya, (string) Str::uuid(), 82));

        $readTarget = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $arjun->id)
            ->where('data->kind', 'new_match')
            ->orderByDesc('created_at')
            ->first();
        if ($readTarget instanceof DatabaseNotification) {
            $readTarget->forceFill(['read_at' => now()])->save();
        }
    }

    /**
     * @param  list<int>  $userIds
     */
    private function deleteFeedNotificationsForUsers(array $userIds): void
    {
        DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->whereIn('notifiable_id', $userIds)
            ->whereIn('data->kind', MemberNotificationFeedService::FEED_KINDS)
            ->delete();
    }

    private function deleteSeedContactRequests(User $priya, User $arjun, User $rohan): void
    {
        ContactRequest::query()
            ->where('request_message', 'like', 'Demo seed:%')
            ->where(function ($q) use ($priya, $arjun, $rohan): void {
                $q->where(
                    static fn($q2) => $q2
                        ->where('from_user_id', $priya->id)
                        ->where('to_user_id', $arjun->id)
                )->orWhere(
                    static fn($q2) => $q2
                        ->where('from_user_id', $rohan->id)
                        ->where('to_user_id', $arjun->id)
                );
            })
            ->delete();
    }
}
