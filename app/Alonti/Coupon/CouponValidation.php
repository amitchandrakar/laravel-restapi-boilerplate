<?php

declare(strict_types=1);


namespace App\Alonti\Coupon;

use App\Models\Offmenu;

class CouponValidation
{
    public function validateCoupon($coupon, $cart)
    {
        $isValid = false;
        if ($coupon) {
            $couponUsage = Offmenu::getCouponUsageCount($coupon);
            if (empty($coupon->usage_limit) || $couponUsage < $coupon->usage_limit) {
                $isValid = true;
            }
            $isValid = self::validateCouponForProductAndVariant($coupon, $cart->items);
        }

        return $isValid;
    }

    public function validateCouponForProductAndVariant($coupon, $items)
    {
        if ($coupon->promotionType->all_orders == 0 && $coupon->promotionType->promotionTypeProduct->isEmpty()) {
            return false;
        }
        $promoVariants = self::getPromoTypeProductVariants($coupon);
        $count = 0;
        foreach ($items as $item) {
            if (self::validatePromoTypeForVariant($promoVariants, $item)) {
                $count++;
            }
        }
        if ($count == 0) {
            return false;
        }

        return true;
    }

    public function validatePromotypeForVariant($promoVariants, $item)
    {
        if (!empty($promoVariants) && !in_array($item->product_variant_id, $promoVariants)) {
            return false;
        }

        return true;
    }

    public function getPromotypeProductVariants($coupon)
    {
        $promoVariants = [];
        if ($coupon->promotionType->promotionTypeProduct->isNotEmpty()) {
            foreach ($coupon->promotionType->promotionTypeProduct as $product) {
                if (isset($product->product_variant_id)) {
                    $promoVariants[] = $product->product_variant_id;
                }
            }
        }

        return $promoVariants;
    }
}
