<?php

declare(strict_types=1);

namespace App\Models;

use App\Alonti\Support\EncryptIdentity;
use App\Mailer\CartInviteeMailer;

class CartInvitee extends BaseModel
{
    use EncryptIdentity;

    protected $table = 'oj_cart_invitees';

    protected static $unguarded = true;

    const RESPONSE_PENDING = 1;

    const RESPONSE_ACCEPTED = 2;

    const RESPONSE_DECLINED = 3;

    const RESPONSE_COMPLETED = 4;

    public function invitee()
    {
        return $this->belongsTo(Invitee::class, 'invitee_id');
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function isPending()
    {
        return $this->response == self::RESPONSE_PENDING;
    }

    public function hasAccepted()
    {
        return $this->response == self::RESPONSE_ACCEPTED;
    }

    public function getDeclineUrlAttribute()
    {
        return url('/group-order/decline/' . $this->encrypted_id);
    }

    public function getAcceptUrlAttribute()
    {
        return url('/group-order/accept/' . $this->encrypted_id);
    }

    public function mailer()
    {
        return new CartInviteeMailer($this);
    }

    public function group()
    {
        return $this->belongsTo(GroupOrder::class, 'group_order_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'invitee_id', 'invitee_id');
    }

    public function orderedCount($cartId = null)
    {
        $moreServeProduct = Product::where('minimum_serve', 10)->pluck('id')->toArray();
        $count = 0;
        if ($cartId) {
            $count += $this->cartItems()
                ->where(['cart_id' => $cartId])
                ->whereIn('product_id', $moreServeProduct)
                ->count();
        }
        $count += $this->cartItems()
            ->where(['cart_id' => $cartId])
            ->whereNotIn('product_id', $moreServeProduct)
            ->withoutAddons()
            ->sum('quantity');

        return $count;
    }

    public function inviteeDefaulMeal($cartId = null)
    {
        $flag = false;
        if ($cartId) {
            $item = $this->cartItems()
                ->where(['cart_id' => $cartId])
                ->where('is_invitee_default_meal', 1)
                ->first();
            if ($item) {
                $flag = true;
            }
        }

        return $flag;
    }

    public function getResponseStatusAttribute()
    {
        switch ($this->response) {
            case 1:
                return 'Pending';
            case 2:
                return 'In progress';
            case 3:
                return 'Declined';
            case 4:
                return 'Ordered';
        }
    }

    public function destroyItems()
    {
        $cartItems = $this->cartItems;
        foreach ($cartItems as $item) {
            $item->options()->delete();
            $item->delete();
        }
    }

    public function isOrdered()
    {
        return $this->cart && $this->cart->order_id && !$this->cart->status;
    }
}
