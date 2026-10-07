<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Support\MailDelivery;
use Illuminate\Notifications\Events\NotificationSending;

class SkipMailWhenDisabledListener
{
    /**
     * Abort the mail channel when non-local env has email disabled / incomplete SMTP.
     * Database (and other) channels are unaffected.
     */
    public function handle(NotificationSending $event): ?bool
    {
        if ($event->channel !== 'mail') {
            return null;
        }

        if (MailDelivery::shouldSendMail()) {
            return null;
        }

        return false;
    }
}
