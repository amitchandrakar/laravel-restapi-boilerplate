<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property Carbon|null $responded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ContactRequest extends BaseModel
{
    protected $table = 'contact_requests';

    protected $fillable = [
        'uuid',
        'from_user_id',
        'to_user_id',
        'request_message',
        'request_status',
        'responded_at',
        'response_message',
    ];

    protected static function booted(): void
    {
        static::creating(static function (ContactRequest $row): void {
            if (!filled($row->uuid)) {
                $row->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public static function existsAccepted(int $fromUserId, int $toUserId): bool
    {
        return static::query()
            ->where('from_user_id', $fromUserId)
            ->where('to_user_id', $toUserId)
            ->where('request_status', 'accepted')
            ->exists();
    }
}
