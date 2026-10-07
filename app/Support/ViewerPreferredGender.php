<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Viewer's partner preferred gender for discovery hard-filters.
 */
final class ViewerPreferredGender
{
    /**
     * Normalized lowercase gender (male|female|other), or null when unset / any.
     */
    public static function forUser(User $viewer): ?string
    {
        $raw = DB::table('user_partner_preferences')->where('user_id', $viewer->id)->value('preferred_gender');

        return self::normalize(is_string($raw) ? $raw : null);
    }

    /**
     * When the viewer has a preferred gender, it overrides any client gender filter.
     *
     * @param  array<string, mixed>  $filters
     *
     * @return array<string, mixed>
     */
    public static function mergeIntoFilters(User $viewer, array $filters): array
    {
        $preferred = self::forUser($viewer);

        if ($preferred !== null) {
            $filters['gender'] = $preferred;
        }

        return $filters;
    }

    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $value = mb_strtolower(trim($raw));

        if ($value === '' || $value === 'any' || $value === 'all') {
            return null;
        }

        if (in_array($value, ['male', 'female', 'other'], true)) {
            return $value;
        }

        return null;
    }
}
