<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Favorite extends Model
{
    use SoftDeletes;

    public const UPDATED_AT = null;

    protected $table = 'favorites';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(static function (Favorite $favorite): void {
            if (!filled($favorite->uuid)) {
                $favorite->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function favoriteUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'favorite_user_id');
    }
}
