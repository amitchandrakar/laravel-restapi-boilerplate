<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends BaseModel
{
    protected $table = 'payments';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(static function (Payment $payment): void {
            if (!filled($payment->uuid)) {
                $payment->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw_response_json' => 'array',
            'paid_at' => 'datetime',
        ];
    }
}
