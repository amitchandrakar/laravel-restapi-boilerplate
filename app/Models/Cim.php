<?php

declare(strict_types=1);

namespace App\Models;

/**
 * @property int|string $profile_id
 */
class Cim extends BaseModel
{
    protected static $unguarded = true;
    // public $timestamps = false;
    // const UPDATED_AT = null;

    public function scopeGetPaymentProfile($query)
    {
        $query->with('PaymentProfile');
    }

    public function paymentProfiles()
    {
        return $this->hasMany(CimPaymentProfile::class, 'profile_id', 'profile_id');
    }
}
