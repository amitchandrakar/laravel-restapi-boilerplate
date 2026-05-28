<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Enums\AdminSettingsType;
use App\Events\AdminSettingsUpdatedEvent;
use App\Jobs\ApplySettingsConfigJob;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractSingletonSettingsService
{
    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    abstract protected function settingsType(): AdminSettingsType;

    /**
     * @return array<string, string> camelCase payload key => database column
     */
    abstract protected function columnMap(): array;

    /**
     * @return list<string> database columns stored encrypted
     */
    abstract protected function secretColumns(): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function toApiArray(Model $record, bool $maskSecrets): array;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->toApiArray($this->resolveInstance(), true);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    public function update(array $data, ?int $actorUserId = null): array
    {
        $record = $this->resolveInstance();
        $secrets = $this->secretColumns();

        foreach ($this->columnMap() as $payloadKey => $column) {
            if (!array_key_exists($payloadKey, $data)) {
                continue;
            }

            $value = $data[$payloadKey];

            if (in_array($column, $secrets, true)) {
                if ($value === null || $value === '' || $value === '***') {
                    continue;
                }
            }

            $record->setAttribute($column, $value);
        }

        if ($actorUserId !== null) {
            $record->setAttribute('updated_by', $actorUserId);
        }

        $record->save();

        AdminSettingsUpdatedEvent::dispatch($this->settingsType(), $actorUserId);
        ApplySettingsConfigJob::dispatch($this->settingsType());

        return $this->all();
    }

    protected function resolveInstance(): Model
    {
        $class = $this->modelClass();

        /** @var callable(): Model $resolver */
        $resolver = [$class, 'instance'];

        return $resolver();
    }

    protected function hasSecret(Model $record, string $column): bool
    {
        $value = $record->getAttribute($column);

        return filled($value);
    }

    /**
     * @return array<string, bool>
     */
    protected function secretFlags(Model $record): array
    {
        $flags = [];

        foreach ($this->secretColumns() as $column) {
            $flags[$this->secretFlagKey($column)] = $this->hasSecret($record, $column);
        }

        return $flags;
    }

    protected function secretFlagKey(string $column): string
    {
        $parts = explode('_', $column);
        $camel = $parts[0];

        foreach (array_slice($parts, 1) as $part) {
            $camel .= ucfirst($part);
        }

        return 'has' . ucfirst($camel);
    }
}
