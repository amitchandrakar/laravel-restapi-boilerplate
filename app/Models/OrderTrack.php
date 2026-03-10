<?php

declare(strict_types=1);


namespace App\Models;

class OrderTrack extends BaseModel
{
    protected static $unguarded = true;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
