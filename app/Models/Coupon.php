<?php

declare(strict_types=1);


namespace App\Models;

class Coupon extends BaseModel
{
    const PERCENTAGE = 1;

    const PRICE = 2;

    const FREE = 3;

    const PRICE_PROMO_TYPE = 32;

    const FREE_DISCOUNT_PROMO_TYPE = 29;

    const FREE_DELIVERY_PROMO_TYPES = [18, 19];

    const FREE_PRODUCT_PROMO_TYPES = [28, 30, 31, 35, 36];

    const ONE_FREE_PRODUCT_PROMO_TYPES = [35, 36];

    public function isPercentageDiscount()
    {
        return $this->promotionType->discount_type === self::PERCENTAGE;
    }

    public function isPriceDiscount()
    {
        return $this->promotionType->discount_type === self::PRICE;
    }

    public function isFreeDiscount()
    {
        return $this->promotionType->discount_type === self::FREE;
    }

    public function isPricePromoType()
    {
        $promotype = PromotionType::where('spl_condition', 'like', '%product_discount%')->pluck('id')->toArray();
        $promotype = $promotype ? array_merge($promotype, [self::PRICE_PROMO_TYPE]) : $promotype;

        return in_array($this->promotion_type_id, $promotype);
    }

    public function isFreeDiscountPromoType()
    {
        return $this->promotion_type_id === self::FREE_DISCOUNT_PROMO_TYPE;
    }

    public function isFreeDiscountPromoTypes()
    {
        return in_array($this->promotion_type_id, self::FREE_DELIVERY_PROMO_TYPES);
    }

    public function isFreePromoTypes()
    {
        return in_array($this->promotion_type_id, self::FREE_PRODUCT_PROMO_TYPES);
    }

    public function isOneFreeProduct()
    {
        return in_array($this->promotion_type_id, self::ONE_FREE_PRODUCT_PROMO_TYPES);
    }

    public static function getDetails($coupon, $cart, $product_id)
    {
        $cafe_id = $cart->cafe_id;
        $today = $cart->shipping ? $cart->shipping->delivery_date : date('Y-m-d');

        $coupon = Coupon::where([
            'coupon' => $coupon,
            'status' => 'active',
        ])
            ->whereRaw('? between start_date and end_date', [$today])
            ->whereHas('couponCafe', function ($query) use ($cafe_id) {
                return $query->where(['cafe_id' => $cafe_id, 'status' => 1]);
            })
            ->with([
                'promotionType' => function ($query) use ($product_id) {
                    return $query->with([
                        'promotionTypeProduct' => function ($query) use ($product_id) {
                            return $query->whereIn('product_id', $product_id)->with(['productSelections']);
                        },
                    ]);
                },
            ])
            ->first();

        return $coupon;
    }

    public static function getCouponByCode($couponCode)
    {
        return Coupon::where('coupon', $couponCode)
            ->with(['promotionType'])
            ->first();
    }

    public function couponCafe()
    {
        return $this->hasMany(CouponCafe::class, 'coupon_id');
    }

    public function promotionType()
    {
        return $this->belongsTo(PromotionType::class, 'promotion_type_id');
    }
}
