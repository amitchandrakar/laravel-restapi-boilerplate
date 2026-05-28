<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdminSettingsType;
use App\Models\RedisSetting;
use App\Services\Concerns\AbstractSingletonSettingsService;
use Illuminate\Database\Eloquent\Model;

class RedisSettingsService extends AbstractSingletonSettingsService
{
    protected function modelClass(): string
    {
        return RedisSetting::class;
    }

    protected function settingsType(): AdminSettingsType
    {
        return AdminSettingsType::Redis;
    }

    /**
     * @return array<string, string>
     */
    protected function columnMap(): array
    {
        return [
            'isEnabled' => 'is_enabled',
            'client' => 'client',
            'host' => 'host',
            'port' => 'port',
            'username' => 'username',
            'password' => 'password',
            'database' => 'database',
            'useTls' => 'use_tls',
            'cachePrefix' => 'cache_prefix',
        ];
    }

    /**
     * @return list<string>
     */
    protected function secretColumns(): array
    {
        return ['password'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toApiArray(Model $record, bool $maskSecrets): array
    {
        /** @var RedisSetting $record */
        return array_merge(
            [
                'isEnabled' => $record->is_enabled,
                'client' => $record->client ?? 'predis',
                'host' => $record->host,
                'port' => $record->port,
                'username' => $record->username,
                'database' => $record->database ?? 0,
                'useTls' => $record->use_tls,
                'cachePrefix' => $record->cache_prefix,
            ],
            $maskSecrets
                ? $this->secretFlags($record)
                : [
                    'password' => $record->password,
                ]
        );
    }
}
