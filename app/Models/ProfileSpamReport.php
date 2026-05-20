<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProfileSpamReport extends BaseModel
{
    protected $table = 'profile_spam_reports';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(static function (ProfileSpamReport $report): void {
            if (!filled($report->uuid)) {
                $report->uuid = (string) Str::uuid();
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }
}
