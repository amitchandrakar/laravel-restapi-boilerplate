<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemHealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $healthCheckService) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.dashboard.view')) {
            return $this->forbiddenResponse();
        }

        $services = $this->healthCheckService->checkServices();
        $healthy = $this->healthCheckService->isHealthy();

        return $this->successResponse(
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
