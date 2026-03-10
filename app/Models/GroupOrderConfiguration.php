<?php

declare(strict_types=1);

namespace App\Models;

class GroupOrderConfiguration extends BaseModel
{
    protected $table = 'group_order_configuration';

    protected static $unguarded = true;

    public function groupOrder()
    {
        return $this->belongsTo(GroupOrder::class, 'group_order_id');
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function cafe()
    {
        return $this->belongsTo(Cafe::class, 'cafe_id');
    }
}
