<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresQueueRetries;
use App\Models\Package;
use App\Services\PackagePermissionService;
use App\Support\QueuePriority;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPackageCandidatePermissionsJob implements ShouldQueue
{
    use ConfiguresQueueRetries;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $packageId)
    {
        $this->onQueue(QueuePriority::low());
    }

    public function handle(PackagePermissionService $packagePermissionService): void
    {
        $package = Package::query()->find($this->packageId);

        if (!($package instanceof Package)) {
            Log::warning('SyncPackageCandidatePermissionsJob: package not found', [
                'package_id' => $this->packageId,
            ]);

            return;
        }

        $packagePermissionService->syncCandidatesForPackage($package);

        Log::info('SyncPackageCandidatePermissionsJob: completed', [
            'package_id' => $this->packageId,
        ]);
    }
}
