<?php

declare(strict_types=1);

namespace App\Models;

use App\Alonti\Cart\CartManager;

class Offmenu extends BaseModel
{
    protected static $unguarded = true;

    public $timestamps = false;

    public static function getCouponUsageCount($coupon)
    {
        return Offmenu::where('coupon_id', $coupon->id)->count();
    }

    public static function updateOffmenu($cart, $orderId)
    {
        $offmenu = Offmenu::where(['order_id' => $orderId, 'flag' => 5])->first();
        if (!$offmenu) {
            $offmenu = new Offmenu();
        }
        $offmenu->order_id = $orderId;
        $offmenu->price = $cart->discount;
        $offmenu->qty = 1;
        $offmenu->txbl = 1;
        $offmenu->flag = 5;
        $offmenu->coupon_id = $cart->coupon_id;
        $offmenu->save();
    }

    public static function deleteOffmenu($orderId)
    {
        $offmenu = Offmenu::where(['order_id' => $orderId, 'flag' => 5])->first();
        if ($offmenu) {
            $offmenu->delete();
        }
    }

    public function offmenuCredit()
    {
        return $this->hasOne(OffmenuCredit::class, 'id', 'offmenu_credit_id');
    }

    public function coupon()
    {
        return $this->hasOne(Coupon::class, 'id', 'coupon_id');
    }

    public function getFlagNameAttribute()
    {
        switch ($this->flag) {
            case 1:
                return 'Off-Menu Item';
            case 2:
                return 'Manager Comps';
            case 3:
                return 'Labor item';
            case 4:
                return 'Rental item';
            case 5:
                return 'Promotional Discount';
            case 6:
                return 'Serving ware option';
        }
    }

    /**
     * Update Offmenu For Serving Unit
     *
     * @param  mixed  $orderId
     * @return void
     */
    public static function updateOffmenuForServingUnit($orderId)
    {
        $cartInfo = app(CartManager::class)->getActiveCart();
        Offmenu::where(['cart_id' => $cartInfo->id, 'flag' => 6])->update(['order_id' => $orderId]);
    }

    public function servingOption()
    {
        return $this->hasOne(ServingOption::class, 'id', 'serving_option_id');
    }
}
