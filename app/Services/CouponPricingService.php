<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Coupon;
use App\Models\Package;

final class CouponPricingService
{
    /**
     * Effective registration price in whole rupees (floored at 0).
     */
    public function apply(Package $package, ?Coupon $coupon): int
    {
        $base = (int) floor($package->catalogRegistrationPayableAmountRupees());

        if ($coupon === null) {
            return max(0, $base);
        }

        if ($coupon->discount_type === 'percent') {
            $percent = min(100, max(0, $coupon->discount_value));
            $discounted = (int) floor(($base * (100 - $percent)) / 100);

            return max(0, $discounted);
        }

        $discounted = $base - $coupon->discount_value;

        return max(0, $discounted);
    }
}
