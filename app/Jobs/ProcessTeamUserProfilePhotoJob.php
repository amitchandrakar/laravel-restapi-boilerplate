<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresQueueRetries;
use App\Models\User;
use App\Services\TeamUserProfilePhotoService;
use App\Support\QueuePriority;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessTeamUserProfilePhotoJob implements ShouldQueue
{
    use ConfiguresQueueRetries;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $tempPath,
        public readonly string $originalName
    ) {
        $this->onQueue(QueuePriority::low());
    }

    public function handle(TeamUserProfilePhotoService $photoService): void
    {
        $user = User::query()->teamUsers()->find($this->userId);

        if (!($user instanceof User)) {
            Log::warning('ProcessTeamUserProfilePhotoJob: team user not found', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        if (!is_readable($this->tempPath)) {
            Log::error('ProcessTeamUserProfilePhotoJob: temp file missing', [
                'user_id' => $this->userId,
                'temp_path' => $this->tempPath,
            ]);

            return;
        }

        try {
            $uploaded = new UploadedFile($this->tempPath, $this->originalName, null, null, true);
            $photoService->store($user, $uploaded);
        } catch (Throwable $e) {
            Log::error('ProcessTeamUserProfilePhotoJob: failed', [
                'user_id' => $this->userId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            if (is_file($this->tempPath)) {
                @unlink($this->tempPath);
            }
        }
    }
}
