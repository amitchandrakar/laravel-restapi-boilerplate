<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\Authenticatable as UserAuthenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends BaseModel implements Authenticatable, AuthorizableContract
{
    use Authorizable, HasApiTokens, HasPermissions, HasRoles, Notifiable, SoftDeletes, UserAuthenticatable;

    protected $table = 'users';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(static function (User $user): void {
            if (!filled($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'deleted_at' => 'datetime',
            'date_of_birth' => 'date',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function hidden(): array
    {
        return ['password'];
    }
}
