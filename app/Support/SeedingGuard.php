<?php

declare(strict_types=1);

namespace App\Support;

/**
 * True while {@see DatabaseSeeder} (or nested seeder runs) executes.
 * Used to skip queued side effects (jobs, outbound notifications listeners, Scout sync jobs)
 * so `migrate --seed` does not write to `jobs` or audit/activity tables.
 */
final class SeedingGuard
{
    private static int $depth = 0;

    public static function begin(): void
    {
        self::$depth++;
    }

    public static function end(): void
    {
        self::$depth = max(0, self::$depth - 1);
    }

    public static function active(): bool
    {
        return self::$depth > 0;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        self::begin();

        try {
            return $callback();
        } finally {
            self::end();
        }
    }
}
