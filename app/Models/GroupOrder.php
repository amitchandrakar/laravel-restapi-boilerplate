<?php

declare(strict_types=1);

namespace App\Models;

use App\Alonti\Support\EncryptIdentity;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupOrder extends BaseModel
{
    use EncryptIdentity, SoftDeletes;

    protected $table = 'oj_group_orders';

    protected static $unguarded = true;

    public function invitees()
    {
        return $this->hasMany(Invitee::class, 'group_order_id');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cart()
    {
        return $this->hasOne(Cart::class, 'group_order_id');
    }

    public function groupOrderConfig()
    {
        return $this->hasOne(GroupOrderConfiguration::class, 'cart_id');
    }

    public function orderedCount($type = [])
    {
        $invitees = $this->cart->invitees;
        if ($type) {
            $invitees = $invitees->whereIn('response', $type);
        }
        $count = 0;
        $count += in_array(4, $type) ? $this->cart->ownerCount : 0;
        foreach ($invitees as $invitee) {
            $count += $invitee->orderedCount($this->cart->id);
        }

        return round($count);
    }

    public function scopeGetGroupOrderDetails($q, $cartInfo)
    {
        return $q->with([
            'cart' => function ($q) use ($cartInfo) {
                $q->where('id', $cartInfo->id);
            },
            'cart.ownerCartItems' => function ($q) {
                $q->withoutAddons();
            },
            'cart.invitees',
            'cart.invitees.invitee',
            'cart.invitees.cartItems' => function ($q) use ($cartInfo) {
                $q->where('cart_id', $cartInfo->id)->withoutAddons();
            },
            'cart.invitees.cartItems.category',
            'cart.invitees.cartItems.product',
            'cart.invitees.cartItems.product.image',
            'cart.invitees.cartItems.variant',
            'cart.invitees.cartItems.variant.image',
            'cart.invitees.cartItems.options',
            'cart.invitees.cartItems.options.option',
            'cart.invitees.cartItems.addons',
            'cart.ownerCartItems.category',
            'cart.ownerCartItems.product',
            'cart.ownerCartItems.product.image',
            'cart.ownerCartItems.variant',
            'cart.ownerCartItems.variant.image',
            'cart.ownerCartItems.options',
            'cart.items.options.option',
            'cart.ownerCartItems.addons',
            'cart.shipping',
            'cart.billing',
        ]);
    }
}
