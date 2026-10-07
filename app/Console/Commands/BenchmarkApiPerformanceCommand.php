<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BenchmarkApiPerformanceCommand extends Command
{
    protected $signature = 'perf:benchmark
                            {--admin-token= : Bearer token for admin routes (optional; skips auth if omitted)}
                            {--output= : Markdown output path (default: storage/app/perf-benchmark-YYYY-MM-DD.md)}';

    protected $description = 'Benchmark admin API endpoints: response time and DB query count';

    /**
     * Network-log baselines captured before optimization (ms).
     *
     * @var array<string, int>
     */
    private const BASELINE_TIME_MS = [
        'system-health' => 2320,
        'subscriptions/expired' => 5620,
        'candidates' => 3110,
    ];

    public function handle(Kernel $kernel): int
    {
        $endpoints = [
            'system-health' => 'GET /api/v1/admin/system-health',
            'subscriptions/expired' => 'GET /api/v1/admin/subscriptions/expired?page=1&perPage=15',
            'candidates' => 'GET /api/v1/admin/candidates?bucket=published&perPage=15',
        ];

        $rows = [];
        $token = (string) $this->option('admin-token');

        foreach ($endpoints as $key => $route) {
            [$method, $path] = explode(' ', $route, 2);
            $measurement = $this->measureEndpoint($kernel, $method, $path, $token);
            $rows[] = [
                'endpoint' => $key,
                'old_time_ms' => self::BASELINE_TIME_MS[$key],
                'new_time_ms' => $measurement['time_ms'],
                'old_queries' => '—',
                'new_queries' => $measurement['queries'],
                'status' => $measurement['status'],
            ];

            $this->line(
                sprintf(
                    '%s: %d ms, %d queries, HTTP %d',
                    $key,
                    $measurement['time_ms'],
                    $measurement['queries'],
                    $measurement['status']
                )
            );
        }

        $markdown = $this->buildMarkdown($rows);
        $outputOption = $this->option('output');
        $output =
            is_string($outputOption) && $outputOption !== ''
                ? $outputOption
                : storage_path('app/perf-benchmark-' . now()->format('Y-m-d') . '.md');
        File::put($output, $markdown);

        $this->info('Report written to: ' . $output);

        return self::SUCCESS;
    }

    /**
     * @return array{time_ms: int, queries: int, status: int}
     */
    private function measureEndpoint(Kernel $kernel, string $method, string $path, string $token): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $request = Request::create($path, $method);
        $request->headers->set('Accept', 'application/json');

        if ($token !== '') {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        $start = hrtime(true);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $timeMs = (int) round((hrtime(true) - $start) / 1_000_000);

        return [
            'time_ms' => $timeMs,
            'queries' => count(DB::getQueryLog()),
            'status' => $response->getStatusCode(),
        ];
    }

    /**
     * @param  list<array{endpoint: string, old_time_ms: int, new_time_ms: int, old_queries: string|int, new_queries: int, status: int}>  $rows
     */
    private function buildMarkdown(array $rows): string
    {
        $lines = [
            '# API Performance Benchmark',
            '',
            'Generated: ' . now()->toIso8601String(),
            '',
            '| Endpoint | Old Time (ms) | New Time (ms) | Old Queries | New Queries | HTTP |',
            '|----------|---------------|---------------|-------------|-------------|------|',
        ];

        foreach ($rows as $row) {
            $lines[] = sprintf(
                '| %s | %d | %d | %s | %d | %d |',
                $row['endpoint'],
                $row['old_time_ms'],
                $row['new_time_ms'],
                (string) $row['old_queries'],
                $row['new_queries'],
                $row['status']
            );
        }

        $lines[] = '';
        $lines[] =
            '_Old times from pre-optimization network logs. Re-run after deploy with a valid `--admin-token` for authenticated routes._';

        return implode("\n", $lines) . "\n";
    }
}
