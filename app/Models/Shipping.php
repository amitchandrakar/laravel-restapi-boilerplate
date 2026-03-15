<?php

declare(strict_types=1);

namespace App\Models;

/**
 * @property \App\Models\Time|null $time
 * @property int|bool|null $paper_products
 */
class Shipping extends BaseModel
{
    protected $table = 'oj_shipping_address';

    protected static $unguarded = true;

    const TYPE_DELIVERY = 1;

    const TYPE_PICKUP = 2;

    public function getPickupAttribute()
    {
        return $this->shipping_type == 2 ? true : false;
    }

    public function getdeliveryAddressAttribute()
    {
        if ($this->address_id) {
            $deliveryAddress = Order::find($this->address_id);
            $address =
                $deliveryAddress->d_addr . ', ' . $deliveryAddress->deliveryCity . ', ' . $deliveryAddress->zipcode;
        } else {
            $state = State::find($this->state);
            $address =
                $this->address1 .
                ', ' .
                $this->address2 .
                ', ' .
                $this->city .
                ', ' .
                $state->name .
                ', ' .
                $this->zipcode;
        }

        return str_replace(', ,', ', ', $address);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function cafe()
    {
        return $this->belongsTo(Cafe::class, 'cafe_id');
    }

    public function setAddress1Attribute($value)
    {
        $value = rtrim($value, ',');
        $this->attributes['address1'] = trim($value);
    }

    public function setAddress2Attribute($value)
    {
        $value = rtrim($value, ',');
        $this->attributes['address2'] = trim($value);
    }

    public function setCityAttribute($value)
    {
        $this->attributes['city'] = trim($value);
    }

    public function setZipcodeAttribute($value)
    {
        $this->attributes['zipcode'] = trim($value);
    }

    public function time()
    {
        return $this->belongsTo(Time::class, 'delivery_time');
    }
}
