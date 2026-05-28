<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdminSettingsType;
use App\Models\SearchSetting;
use App\Services\Concerns\AbstractSingletonSettingsService;
use Illuminate\Database\Eloquent\Model;

class SearchSettingsService extends AbstractSingletonSettingsService
{
    protected function modelClass(): string
    {
        return SearchSetting::class;
    }

    protected function settingsType(): AdminSettingsType
    {
        return AdminSettingsType::Search;
    }

    /**
     * @return array<string, string>
     */
    protected function columnMap(): array
    {
        return [
            'isEnabled' => 'is_enabled',
            'driver' => 'driver',
            'appId' => 'app_id',
            'adminApiKey' => 'admin_api_key',
            'searchApiKey' => 'search_api_key',
            'candidateIndexName' => 'candidate_index_name',
            'queueIndexing' => 'queue_indexing',
        ];
    }

    /**
     * @return list<string>
     */
    protected function secretColumns(): array
    {
        return ['admin_api_key', 'search_api_key'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toApiArray(Model $record, bool $maskSecrets): array
    {
        /** @var SearchSetting $record */
        return array_merge(
            [
                'isEnabled' => $record->is_enabled,
                'driver' => $record->driver ?? 'algolia',
                'appId' => $record->app_id,
                'candidateIndexName' => $record->candidate_index_name,
                'queueIndexing' => $record->queue_indexing,
            ],
            $maskSecrets
                ? $this->secretFlags($record)
                : [
                    'adminApiKey' => $record->admin_api_key,
                    'searchApiKey' => $record->search_api_key,
                ]
        );
    }
}
