<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Package;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PackageUpdatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Package $package) {}
}
