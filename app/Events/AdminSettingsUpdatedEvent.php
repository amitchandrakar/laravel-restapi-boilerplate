<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\AdminSettingsType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminSettingsUpdatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AdminSettingsType $settingType,
        public readonly ?int $actorUserId = null
    ) {}
}
