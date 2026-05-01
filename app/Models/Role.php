<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Extended Spatie role (see migrations extending `roles`).
 *
 * @property string|null $uuid
 * @property string|null $title
 * @property string|null $description
 * @property bool $is_system
 * @property bool $is_default_registration
 */
class Role extends SpatieRole
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_default_registration' => 'boolean',
        ];
    }
}
