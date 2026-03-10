<?php

declare(strict_types=1);


namespace App\Models;

class CimPaymentProfile extends BaseModel
{
    protected static $unguarded = true;

    // public $timestamps = false;
    // const UPDATED_AT = null;
    //
    public function cim()
    {
        return $this->belongsTo(Cim::class, 'profile_id');
    }

    public function paids()
    {
        return $this->hasMany(CimPaid::class, 'payment_profile_id', 'payment_profile_id');
    }

    public function billingAddress()
    {
        return $this->hasOne(UserCreditCardAddress::class, 'user_cc_id');
    }
}
