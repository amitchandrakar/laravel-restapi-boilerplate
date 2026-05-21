<?php

declare(strict_types=1);

namespace App\Support;

final class CacheKeys
{
    public static function dashboardMetricsOverview(): string
    {
        return 'dashboard:metrics:overview';
    }

    public static function candidateProfileOptions(): string
    {
        return 'master:candidate-profile-options';
    }

    public static function publicFeaturedPage(int $page, int $perPage): string
    {
        return 'profiles:featured:page:' . $page . ':per:' . $perPage;
    }

    public static function userPermissions(int $userId): string
    {
        return 'user:' . $userId . ':permissions';
    }

    public static function candidateImportBatch(string $importId): string
    {
        return 'admin:candidate-import:' . $importId . ':batch';
    }

    public static function candidateImportRows(string $importId): string
    {
        return 'admin:candidate-import:' . $importId . ':rows';
    }
}
