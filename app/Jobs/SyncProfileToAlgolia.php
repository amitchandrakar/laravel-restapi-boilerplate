<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresQueueRetries;
use App\Models\User;
use App\Support\QueuePriority;
use App\Support\ScoutConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProfileToAlgolia implements ShouldQueue
{
    use ConfiguresQueueRetries;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $userId)
    {
        $this->onQueue(QueuePriority::low());
    }

    public function handle(): void
    {
        if (!ScoutConfig::usesAlgolia()) {
            return;
        }

        $user = User::query()->find($this->userId);
        if (!$user instanceof User) {
            return;
        }

        if ($user->shouldBeSearchable()) {
            $user->searchable();

            return;
        }

        $user->unsearchable();
    }
}
