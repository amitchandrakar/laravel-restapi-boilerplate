<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdminSettingsType;
use App\Models\SiteSetting;
use App\Services\Concerns\AbstractSingletonSettingsService;
use App\Support\CacheKeys;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSettingsService extends AbstractSingletonSettingsService
{
    protected function modelClass(): string
    {
        return SiteSetting::class;
    }

    protected function settingsType(): AdminSettingsType
    {
        return AdminSettingsType::Site;
    }

    /**
     * @return array<string, string>
     */
    protected function columnMap(): array
    {
        return [
            'siteName' => 'site_name',
            'logoUrl' => 'logo_url',
            'faviconUrl' => 'favicon_url',
            'contactEmail' => 'contact_email',
            'contactPhone' => 'contact_phone',
            'contactAddress' => 'contact_address',
            'allowedCommunitySurnames' => 'allowed_community_surnames',
            'maintenanceMode' => 'maintenance_mode',
            'requireProfileApproval' => 'require_profile_approval',
            'successStoriesCount' => 'success_stories_count',
        ];
    }

    /**
     * @return list<string>
     */
    protected function secretColumns(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toApiArray(Model $record, bool $_maskSecrets): array
    {
        /** @var SiteSetting $record */
        return [
            'siteName' => $record->site_name,
            'logoUrl' => $record->logo_url,
            'faviconUrl' => $record->favicon_url,
            'contactEmail' => $record->contact_email,
            'contactPhone' => $record->contact_phone,
            'contactAddress' => $record->contact_address,
            'allowedCommunitySurnames' => $record->allowed_community_surnames ?? [],
            'maintenanceMode' => $record->maintenance_mode,
            'requireProfileApproval' => $record->require_profile_approval,
            'successStoriesCount' => $record->success_stories_count ?? 0,
        ];
    }

    /**
     * Safe subset for unauthenticated public website consumers.
     *
     * @return array<string, mixed>
     */
    public function toPublicApiArray(): array
    {
        $all = $this->all();

        return [
            'siteName' => $all['siteName'] ?? null,
            'logoUrl' => $all['logoUrl'] ?? null,
            'faviconUrl' => $all['faviconUrl'] ?? null,
            'contactEmail' => $all['contactEmail'] ?? null,
            'contactPhone' => $all['contactPhone'] ?? null,
            'contactAddress' => $all['contactAddress'] ?? null,
            'allowedCommunitySurnames' => $all['allowedCommunitySurnames'] ?? [],
            'successStoriesCount' => $all['successStoriesCount'] ?? 0,
            'maintenanceMode' => (bool) ($all['maintenanceMode'] ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cachedPublicApiArray(): array
    {
        $ttl = max(60, (int) config('cache_strategy.site_settings_seconds', 3600));

        /** @var array<string, mixed> $data */
        $data = Cache::remember(CacheKeys::publicSiteSettings(), $ttl, fn(): array => $this->toPublicApiArray());

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function cachedAll(): array
    {
        $ttl = max(60, (int) config('cache_strategy.site_settings_seconds', 3600));

        /** @var array<string, mixed> $data */
        $data = Cache::remember(CacheKeys::adminSiteSettings(), $ttl, fn(): array => $this->all());

        return $data;
    }

    public function forgetCaches(): void
    {
        Cache::forget(CacheKeys::publicSiteSettings());
        Cache::forget(CacheKeys::adminSiteSettings());
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    public function update(array $data, ?int $actorUserId = null): array
    {
        $updated = parent::update($data, $actorUserId);
        $this->forgetCaches();

        return $updated;
    }
}
