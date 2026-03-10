<?php

declare(strict_types=1);


namespace App\Models;

class CimPaid extends BaseModel
{
    protected static $unguarded = true;
    // public $timestamps = false;
    // const UPDATED_AT = null;

    public function isAuthorizedOrPaid()
    {
        return in_array($this->status, ['Authorized', 'Paid']);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
