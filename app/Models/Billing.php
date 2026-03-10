<?php

declare(strict_types=1);


namespace App\Models;

class Billing extends BaseModel
{
    protected $table = 'oj_billing_address';

    protected static $unguarded = true;

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }
}
