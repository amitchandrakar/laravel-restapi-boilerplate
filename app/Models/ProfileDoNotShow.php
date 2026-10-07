<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProfileDoNotShow extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'profile_do_not_show';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(static function (ProfileDoNotShow $row): void {
            if (!filled($row->uuid)) {
                $row->uuid = (string) Str::uuid();
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function hiddenUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_user_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
