<?php

declare(strict_types=1);

namespace App\Support;

final class ScoutConfig
{
    public static function driver(): string
    {
        return (string) config('scout.driver', 'collection');
    }

    public static function usesAlgolia(): bool
    {
        return self::driver() === 'algolia' &&
            filled(config('scout.algolia.id')) &&
            filled(config('scout.algolia.secret'));
    }

    public static function candidateIndexName(): string
    {
        $prefix = (string) config('scout.prefix', '');
        $name = (string) config('scout.candidate_index', 'candidates');

        return $prefix . $name;
    }
}
