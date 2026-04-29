<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\Authenticatable as UserAuthenticatable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends BaseModel implements Authenticatable
{
    use HasApiTokens, HasPermissions, HasRoles, Notifiable, UserAuthenticatable;

    const CREATED_AT = 'creation_date';

    const UPDATED_AT = 'last_updated';

    protected $table = 'alonti_users';

    protected static $unguarded = true;
}
