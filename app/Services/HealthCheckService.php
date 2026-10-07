<?php

declare(strict_types=1);

namespace App\Services;

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Support\ScoutConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

final class HealthCheckService
{
    /**
     * Fast probes suitable for synchronous API responses (no external network I/O).
     *
     * @return array<string, array{status: string, message: string}>
     */
    public function checkServicesFast(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkApplicationStorage(),
            'object_storage' => [
                'status' => 'up',
                'message' => 'Deep object storage probe deferred (run health:warm-cache)',
            ],
            'search' => [
                'status' => 'up',
                'message' => 'Deep search probe deferred (run health:warm-cache)',
            ],
        ];
    }

    /**
     * Full probes including Algolia and S3 — use via health:warm-cache, not per request.
     *
     * @return array<string, array{status: string, message: string}>
     */
    public function checkServicesDeep(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkApplicationStorage(),
            'object_storage' => $this->checkObjectStorageDisk(),
            'search' => $this->checkAlgolia(),
        ];
    }

    /**
     * @return array<string, array{status: string, message: string}>
     */
    public function checkServices(): array
    {
        return $this->checkServicesDeep();
    }

    public function isHealthy(): bool
    {
        return collect($this->checkServices())->every(static fn(array $service): bool => $service['status'] === 'up');
    }

    public function isHealthyFast(): bool
    {
        return collect($this->checkServicesFast())->every(
            static fn(array $service): bool => $service['status'] === 'up'
        );
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'up', 'message' => 'Database connection is healthy'];
        } catch (\Throwable $e) {
            return [
                'status' => 'down',
                'message' => config('app.debug') ? $e->getMessage() : 'Database connection failed',
            ];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 10);
            $canRead = Cache::get('health_check') === true;
            Cache::forget('health_check');

            if ($canRead) {
                return ['status' => 'up', 'message' => 'Cache connection is healthy'];
            }

            return ['status' => 'down', 'message' => 'Cache read failed'];
        } catch (\Throwable $e) {
            return [
                'status' => 'down',
                'message' => config('app.debug') ? $e->getMessage() : 'Cache connection failed',
            ];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkQueue(): array
    {
        try {
            $connection = (string) config('queue.default', 'sync');
            $size = Queue::connection()->size();

            return [
                'status' => 'up',
                'message' => 'Queue connection is healthy (' . $connection . ', depth ' . $size . ')',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'down',
                'message' => config('app.debug') ? $e->getMessage() : 'Queue connection failed',
            ];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkApplicationStorage(): array
    {
        try {
            $path = storage_path('logs');
            $writable = is_writable($path);

            return $writable
                ? ['status' => 'up', 'message' => 'Local storage is writable']
                : ['status' => 'down', 'message' => 'Local storage is not writable'];
        } catch (\Throwable $e) {
            return [
                'status' => 'down',
                'message' => config('app.debug') ? $e->getMessage() : 'Storage check failed',
            ];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkAlgolia(): array
    {
        if (!ScoutConfig::usesAlgolia()) {
            return [
                'status' => 'up',
                'message' => 'Algolia not configured (driver: ' . ScoutConfig::driver() . ')',
            ];
        }

        try {
            $client = SearchClient::create(
                (string) config('scout.algolia.id'),
                (string) config('scout.algolia.secret')
            );
            $client->listIndices();

            return ['status' => 'up', 'message' => 'Algolia API is reachable'];
        } catch (\Throwable $e) {
            return [
                'status' => 'down',
                'message' => config('app.debug') ? $e->getMessage() : 'Algolia API check failed',
            ];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkObjectStorageDisk(): array
    {
        $diskName = (string) config('user_images.disk', 'user_profile_images');
        $driver = (string) config('filesystems.disks.' . $diskName . '.driver', 'local');

        if ($driver !== 's3') {
            return [
                'status' => 'up',
                'message' => 'Object storage using local disk (' . $diskName . ')',
            ];
        }

        try {
            $disk = Storage::disk($diskName);
            $probeKey = '.health/' . uniqid('probe_', true);
            $disk->put($probeKey, 'ok');
            $disk->delete($probeKey);

            return ['status' => 'up', 'message' => 'S3 disk is reachable (' . $diskName . ')'];
        } catch (\Throwable $e) {
            return [
                'status' => 'down',
                'message' => config('app.debug') ? $e->getMessage() : 'S3 disk check failed',
            ];
        }
    }
}
