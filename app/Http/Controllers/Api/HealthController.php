<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     */
    public function check(): JsonResponse
    {
        $checks = [
            'status' => 'up',
            'timestamp' => now()->toIso8601String(),
            'services' => $this->checkServices(),
        ];

        $allHealthy = collect($checks['services'])->every(fn($service) => $service['status'] === 'up');

        return response()->json($checks, $allHealthy ? 200 : 503);
    }

    /**
     * Check all services
     */
    protected function checkServices(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];
    }

    /**
     * Check database connection
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $status = 'up';
            $message = 'Database connection is healthy';
        } catch (\Exception $e) {
            $status = 'down';
            $message = config('app.debug') ? $e->getMessage() : 'Database connection failed';
        }

        return [
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Check cache connection
     */
    protected function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 10);
            $canRead = Cache::get('health_check') === true;
            Cache::forget('health_check');

            if ($canRead) {
                $status = 'up';
                $message = 'Cache connection is healthy';
            } else {
                $status = 'down';
                $message = 'Cache read failed';
            }
        } catch (\Exception $e) {
            $status = 'down';
            $message = config('app.debug') ? $e->getMessage() : 'Cache connection failed';
        }

        return [
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Check storage accessibility
     */
    protected function checkStorage(): array
    {
        try {
            $path = storage_path('logs');
            $writable = is_writable($path);

            if ($writable) {
                $status = 'up';
                $message = 'Storage is writable';
            } else {
                $status = 'down';
                $message = 'Storage is not writable';
            }
        } catch (\Exception $e) {
            $status = 'down';
            $message = config('app.debug') ? $e->getMessage() : 'Storage check failed';
        }

        return [
            'status' => $status,
            'message' => $message,
        ];
    }
}
