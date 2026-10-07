<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SubscriptionExpiryService;
use Illuminate\Console\Command;

class ExpireSubscriptionsToParichayCommand extends Command
{
    protected $signature = 'subscriptions:expire-to-parichay';

    protected $description = 'Expire active paid subscriptions past ends_at and activate Parichay free';

    public function handle(SubscriptionExpiryService $service): int
    {
        $result = $service->expirePaidAndActivateParichay();

        $this->info(
            sprintf(
                'Expired %d subscription(s); activated/renewed Parichay for %d user(s).',
                $result['expired'],
                $result['parichay_activated']
            )
        );

        return self::SUCCESS;
    }
}
