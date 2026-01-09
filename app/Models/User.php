<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'dob',
        'company_name',
        'salary',
        'contact_number',
        'status',
        'last_login_at',
        'last_login_ip',
        'login_attempts',
        'is_locked',
        'locked_at',
        'account_type',
        'two_factor_enabled',
        'two_factor_secret',
        'password_changed_at',
        'password_expires_at',
        'email_notifications',
        'sms_notifications',
        'dark_mode',
        'marketing_opt_in',
        'created_from_ip',
        'updated_from_ip',
        'user_agent',
        'created_by',
        'updated_by',
    ];

    /**
     * Hidden attributes
     */
    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    /**
     * Attribute casting
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
            'last_login_at' => 'datetime',
            'locked_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password_expires_at' => 'datetime',
            'is_locked' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'dark_mode' => 'boolean',
            'marketing_opt_in' => 'boolean',
            'salary' => 'decimal:2',
        ];
    }

    /**
     * Use UUID for route model binding
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Audit relationships
     */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    /**
     * Role names accessor (safe for APIs)
     */
    public function getRoleNamesAttribute(): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    /**
     * Permission names accessor
     */
    public function getPermissionNamesAttribute(): array
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }
}
