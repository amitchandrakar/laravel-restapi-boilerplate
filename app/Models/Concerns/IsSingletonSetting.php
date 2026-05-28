<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait IsSingletonSetting
{
    public static function instance(): static
    {
        /** @var static $record */
        $record = static::query()->firstOrCreate(['id' => 1], ['uuid' => (string) Str::uuid()]);

        return $record;
    }

    protected static function bootIsSingletonSetting(): void
    {
        static::creating(static function (self $model): void {
            if (!filled($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
