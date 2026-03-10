<?php

declare(strict_types=1);

namespace App\Models;

class Time extends BaseModel
{
    public function order()
    {
        return $this->hasMany(Order::class, 'time_id');
    }

    public function shipping()
    {
        return $this->hasMany(Shipping::class, 'delivery_time');
    }
}
