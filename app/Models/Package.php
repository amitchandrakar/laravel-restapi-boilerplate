<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Package extends BaseModel
{
    use SoftDeletes;

    protected $table = 'packages';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(static function (Package $package): void {
            if (!filled($package->uuid)) {
                $package->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'package_permissions',
            'package_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_unit' => 'string',
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'price' => 'decimal:2',
            'discounted_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_default_registration' => 'boolean',
            'is_popular' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }
}
