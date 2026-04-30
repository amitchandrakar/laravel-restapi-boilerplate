<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLifecycleEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly User $user, public readonly string $action) {}
}
