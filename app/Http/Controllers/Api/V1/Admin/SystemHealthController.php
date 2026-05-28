<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\HealthCheckService;
use App\Support\CacheKeys;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemHealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $healthCheckService) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.dashboard.view')) {
            return $this->forbiddenResponse();
        }

        $ttl = max(60, (int) config('cache_strategy.dashboard_health_seconds', 3600));

        $payload = Cache::remember(CacheKeys::dashboardSystemHealth(), $ttl, function (): array {
            $services = $this->healthCheckService->checkServices();
            $healthy = $this->healthCheckService->isHealthy();

            return [
                'status' => $healthy ? 'up' : 'degraded',
                'timestamp' => now()->utc()->format('Y-m-d\TH:i:s.v\Z'),
                'services' => $services,
                'httpStatus' => $healthy ? 200 : 503,
            ];
        });

        $httpStatus = $payload['httpStatus'];
        unset($payload['httpStatus']);

        return $this->successResponse(
            $payload,
            $httpStatus === 200 ? 'All systems operational' : 'One or more services are unhealthy',
            $httpStatus
        );
    }
}
