<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicSiteSettingsController extends Controller
{
    public function __construct(private readonly SiteSettingsService $siteSettingsService) {}

    public function show(): JsonResponse
    {
        $ttl = max(60, (int) config('cache_strategy.featured_profiles_seconds', 300));
        $cacheKey = 'public:site-settings:v1';

        /** @var array<string, mixed> $data */
        $data = Cache::remember($cacheKey, $ttl, fn(): array => $this->siteSettingsService->toPublicApiArray());

        return $this->successResponse($data, 'Site settings fetched successfully');
    }
}
