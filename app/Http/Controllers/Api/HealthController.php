<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HealthCheckService;
use App\Support\ApiResponseBuilder;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $healthCheckService) {}

    /**
     * Lightweight health check (database + cache).
     */
    public function check(): JsonResponse
    {
        $services = [
            'database' => $this->healthCheckService->checkDatabase(),
            'cache' => $this->healthCheckService->checkCache(),
        ];
        $healthy = collect($services)->every(static fn(array $s): bool => $s['status'] === 'up');

        return ApiResponseBuilder::success(
            [
                'status' => $healthy ? 'up' : 'degraded',
                'timestamp' => now()->utc()->format('Y-m-d\TH:i:s.v\Z'),
                'services' => $services,
            ],
            $healthy ? 'OK' : 'Service degraded',
            $healthy ? 200 : 503
        );
    }

    /**
     * Detailed health check for ops (database, cache, queue, storage).
     */
    public function detailed(): JsonResponse
    {
        $services = $this->healthCheckService->checkServices();
        $healthy = $this->healthCheckService->isHealthy();

        return ApiResponseBuilder::success(
            [
                'status' => $healthy ? 'up' : 'degraded',
                'timestamp' => now()->utc()->format('Y-m-d\TH:i:s.v\Z'),
                'services' => $services,
            ],
            $healthy ? 'All systems operational' : 'One or more services are unhealthy',
            $healthy ? 200 : 503
        );
    }
}
