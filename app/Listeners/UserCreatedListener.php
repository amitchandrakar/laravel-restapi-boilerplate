<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserCreatedEvent;
use App\Notifications\WelcomeEmailNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UserCreatedListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserCreatedEvent $event): void
    {
        $event->user->notify(new WelcomeEmailNotification());

        // SMS provider is not configured yet; keep a hook here for future.
        Log::info('Welcome SMS not configured (skipped).', [
            'user_id' => $event->user->id,
        ]);
    }
}
