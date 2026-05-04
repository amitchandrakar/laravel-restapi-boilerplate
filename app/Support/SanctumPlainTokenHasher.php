<?php

declare(strict_types=1);

namespace App\Support;

final class SanctumPlainTokenHasher
{
    public static function hashPlainTextToken(string $plainTextToken): string
    {
        if ($plainTextToken === '') {
            return '';
        }
        $parts = explode('|', $plainTextToken, 2);
        $tokenValue = $parts[1] ?? $parts[0];

        return hash('sha256', $tokenValue);
    }
}
