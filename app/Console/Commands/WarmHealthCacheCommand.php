<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\HealthCheckService;
use App\Support\CacheKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmHealthCacheCommand extends Command
{
    protected $signature = 'health:warm-cache';

    protected $description = 'Run deep service health probes and refresh the admin system-health cache';

    public function handle(HealthCheckService $healthCheckService): int
    {
        $ttl = max(60, (int) config('cache_strategy.dashboard_health_seconds', 3600));
        $services = $healthCheckService->checkServicesDeep();
        $healthy = collect($services)->every(static fn(array $service): bool => $service['status'] === 'up');

        Cache::put(
            CacheKeys::dashboardSystemHealth(),
            [
                'status' => $healthy ? 'up' : 'degraded',
                'timestamp' => now()->utc()->format('Y-m-d\TH:i:s.v\Z'),
                'services' => $services,
                'httpStatus' => $healthy ? 200 : 503,
            ],
            $ttl
        );

        $this->info('System health cache warmed (' . ($healthy ? 'up' : 'degraded') . ').');

        return self::SUCCESS;
    }
}
