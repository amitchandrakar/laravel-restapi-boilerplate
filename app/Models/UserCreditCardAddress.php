<?php

declare(strict_types=1);

namespace App\Models;

class UserCreditCardAddress extends BaseModel
{
    protected static $unguarded = true;

    protected $table = 'user_credit_card_address';

    public function cimPaymentProfile()
    {
        return $this->belongsTo(CimPaymentProfile::class, 'user_cc_id');
    }
}
