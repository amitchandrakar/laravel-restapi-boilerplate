<?php

declare(strict_types=1);

namespace App\Support;

final class QueuePriority
{
    public static function critical(): string
    {
        return (string) config('queue_priorities.critical', 'critical');
    }

    public static function high(): string
    {
        return (string) config('queue_priorities.high', 'high');
    }

    public static function default(): string
    {
        return (string) config('queue_priorities.default', 'default');
    }

    public static function low(): string
    {
        return (string) config('queue_priorities.low', 'low');
    }
}
