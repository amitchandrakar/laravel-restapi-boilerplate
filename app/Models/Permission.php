<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Extended Spatie permission (see migrations extending `permissions`).
 *
 * @property string|null $uuid
 * @property int|null $module_id
 * @property string|null $title
 */
class Permission extends SpatiePermission
{
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(
            Package::class,
            'package_permissions',
            'permission_id',
            'package_id'
        )->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
