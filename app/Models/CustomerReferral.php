<?php

declare(strict_types=1);

namespace App\Models;

class CustomerReferral extends BaseModel
{
    protected $table = 'customer_referrals';

    protected static $unguarded = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class, 'customer_referral_id');
    }
}
