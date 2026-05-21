<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class QuerySearch
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $columns
     */
    public static function whereContainsAny(Builder $query, array $columns, string $needle): void
    {
        $needle = trim($needle);

        if ($needle === '') {
            return;
        }

        $query->where(static function (Builder $group) use ($columns, $needle): void {
            foreach ($columns as $index => $column) {
                self::applyContains($group, $column, $needle, $index === 0 ? 'where' : 'orWhere');
            }
        });
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    private static function applyContains(
        Builder $query,
        string $column,
        string $needle,
        string $boolean = 'where'
    ): void {
        self::assertSafeColumn($column);

        $connection = $query->getConnection();
        $driver = $connection instanceof Connection ? $connection->getDriverName() : 'mysql';

        if ($driver === 'sqlite') {
            $query->{$boolean . 'Raw'}('instr(LOWER(' . $column . '), LOWER(?)) > 0', [$needle]);

            return;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $needle);
        $query->{$boolean}($column, 'like', '%' . $escaped . '%', '\\');
    }

    private static function assertSafeColumn(string $column): void
    {
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $column)) {
            throw new InvalidArgumentException('Invalid search column: ' . $column);
        }
    }
}
