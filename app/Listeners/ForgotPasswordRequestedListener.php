<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ForgotPasswordRequestedEvent;
use App\Notifications\ForgotPasswordNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class ForgotPasswordRequestedListener implements ShouldQueue
{
    public function handle(ForgotPasswordRequestedEvent $event): void
    {
        $event->user->notify(new ForgotPasswordNotification($event->resetHash));
    }
}

