<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\PackageCreatedEvent;
use App\Models\Package;
use App\Support\SeedingGuard;

class PackageObserver
{
    public function created(Package $package): void
    {
        if (SeedingGuard::active()) {
            return;
        }

        PackageCreatedEvent::dispatch($package);
    }
}
