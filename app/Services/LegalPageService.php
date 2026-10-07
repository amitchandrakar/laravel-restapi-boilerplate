<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdminSettingsType;
use App\Events\AdminSettingsUpdatedEvent;
use App\Jobs\ApplySettingsConfigJob;
use App\Models\LegalPage;
use App\Support\CacheKeys;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LegalPageService
{
    /**
     * @return Collection<int, LegalPage>
     */
    public function list(): Collection
    {
        return LegalPage::orderBy('slug')->get();
    }

    public function findBySlug(string $slug): ?LegalPage
    {
        return LegalPage::query()->where('slug', $slug)->first();
    }

    public function findPublishedBySlug(string $slug): ?LegalPage
    {
        $ttl = max(60, (int) config('cache_strategy.legal_pages_seconds', 600));

        /** @var LegalPage|null $page */
        $page = Cache::remember(
            CacheKeys::publicLegalPage($slug),
            $ttl,
            fn(): ?LegalPage => LegalPage::query()->where('slug', $slug)->where('is_published', true)->first()
        );

        return $page;
    }

    public function forgetPublishedCache(string $slug): void
    {
        Cache::forget(CacheKeys::publicLegalPage($slug));
    }

    public function forgetAllPublishedCaches(): void
    {
        foreach ($this->list() as $page) {
            $this->forgetPublishedCache($page->slug);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    public function update(LegalPage $page, array $data, ?int $actorUserId = null): array
    {
        $map = [
            'title' => 'title',
            'body' => 'body',
            'version' => 'version',
            'isPublished' => 'is_published',
            'publishedAt' => 'published_at',
        ];

        foreach ($map as $payloadKey => $column) {
            if (!array_key_exists($payloadKey, $data)) {
                continue;
            }

            $page->setAttribute($column, $data[$payloadKey]);
        }

        if ($actorUserId !== null) {
            $page->setAttribute('updated_by', $actorUserId);
        }

        $page->save();
        $this->forgetPublishedCache($page->slug);

        AdminSettingsUpdatedEvent::dispatch(AdminSettingsType::LegalPage, $actorUserId);
        ApplySettingsConfigJob::dispatch(AdminSettingsType::LegalPage);

        return $this->toApiArray($page);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(LegalPage $page): array
    {
        return [
            'slug' => $page->slug,
            'title' => $page->title,
            'body' => $page->body,
            'version' => $page->version,
            'isPublished' => $page->is_published,
            'publishedAt' => $page->published_at !== null ? $page->published_at->toIso8601String() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicApiArray(LegalPage $page): array
    {
        return [
            'slug' => $page->slug,
            'title' => $page->title,
            'body' => $page->body,
            'version' => $page->version,
            'publishedAt' => $page->published_at !== null ? $page->published_at->toIso8601String() : null,
        ];
    }
}
