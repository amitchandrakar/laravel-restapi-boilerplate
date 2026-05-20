<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\SearchableCandidateProfile;
use Illuminate\Auth\Authenticatable as UserAuthenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property Carbon|null $date_of_birth
 */
class User extends BaseModel implements Authenticatable, AuthorizableContract
{
    use Authorizable, HasApiTokens, HasPermissions, HasRoles, Notifiable, SearchableCandidateProfile, SoftDeletes, UserAuthenticatable;

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
            'completed_sections_json' => 'array',
            'interests' => 'array',
            'movie_genres' => 'array',
            'hobbies' => 'array',
            'likes' => 'array',
            'dislikes' => 'array',
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'featured_at' => 'datetime',
            'featured_by' => 'integer',
            'phone_alerts_enabled' => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'show_online_status' => 'boolean',
            'hide_phone_number' => 'boolean',
            'birth_country_id' => 'integer',
            'birth_state_id' => 'integer',
            'birth_city_id' => 'integer',
            'occupation_id' => 'integer',
            'income_range_id' => 'integer',
            'current_country_id' => 'integer',
            'current_state_id' => 'integer',
            'current_city_id' => 'integer',
            'maternal_country_id' => 'integer',
            'maternal_state_id' => 'integer',
            'maternal_city_id' => 'integer',
        ];
    }

    public function scopeCandidates($query)
    {
        return $query->whereHas('primaryRole', static function (Builder $builder): void {
            $builder->where('name', 'candidate');
        });
    }

    public function scopeTeamUsers($query)
    {
        return $query->whereHas('primaryRole', static function (Builder $builder): void {
            $builder->where('name', '!=', 'candidate');
        });
    }

    public function primaryRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(UserVerificationDocument::class, 'user_id');
    }

    /**
     * @return array<int, string>
     */
    protected function hidden(): array
    {
        return ['password'];
    }
}
