<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdminSettingsType;
use App\Models\StorageSetting;
use App\Services\Concerns\AbstractSingletonSettingsService;
use Illuminate\Database\Eloquent\Model;

class StorageSettingsService extends AbstractSingletonSettingsService
{
    protected function modelClass(): string
    {
        return StorageSetting::class;
    }

    protected function settingsType(): AdminSettingsType
    {
        return AdminSettingsType::Storage;
    }

    /**
     * @return array<string, string>
     */
    protected function columnMap(): array
    {
        return [
            'isEnabled' => 'is_enabled',
            'driver' => 'driver',
            'bucket' => 'bucket',
            'region' => 'region',
            'accessKeyId' => 'access_key_id',
            'secretAccessKey' => 'secret_access_key',
            'endpoint' => 'endpoint',
            'url' => 'url',
            'usePathStyleEndpoint' => 'use_path_style_endpoint',
        ];
    }

    /**
     * @return list<string>
     */
    protected function secretColumns(): array
    {
        return ['secret_access_key'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toApiArray(Model $record, bool $maskSecrets): array
    {
        /** @var StorageSetting $record */
        return array_merge(
            [
                'isEnabled' => $record->is_enabled,
                'driver' => $record->driver ?? 's3',
                'bucket' => $record->bucket,
                'region' => $record->region,
                'accessKeyId' => $record->access_key_id,
                'endpoint' => $record->endpoint,
                'url' => $record->url,
                'usePathStyleEndpoint' => $record->use_path_style_endpoint,
            ],
            $maskSecrets
                ? $this->secretFlags($record)
                : [
                    'secretAccessKey' => $record->secret_access_key,
                ]
        );
    }
}
