<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\PackageCreatedEvent;
use App\Models\Package;

class PackageObserver
{
    public function created(Package $package): void
    {
        PackageCreatedEvent::dispatch($package);
    }
}
