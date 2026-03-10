<?php

declare(strict_types=1);


namespace App\Alonti\Coupon;

use App\Alonti\Cart\CartManager;
use App\Models\Coupon;
use App\Models\Offmenu;

class ResetCoupon
{
    public function __construct()
    {
        $this->cart = app(CartManager::class)->getActiveCart();
    }

    public function resetPromocode($couponCode)
    {
        $coupon = Coupon::getCouponByCode($couponCode);
        if (!$this->cart || !$coupon) {
            $message = 'Cart not found!';

            return app(CouponResponse::class)->getFailureResponse($message);
        }
        $this->cart->deleteCartDiscount($coupon);
        $message = 'Promo code has been removed successfully';
        $data['cart'] = app(CouponManager::class)->getCartInfo($this->cart);

        return app(CouponResponse::class)->getSuccessResponse($message, $data);
    }

    public function deleteOffmenuDiscount()
    {
        if ($this->cart->order) {
            Offmenu::deleteOffmenu($this->cart->order_id);
        }
    }
}
