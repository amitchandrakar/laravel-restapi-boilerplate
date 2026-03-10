<?php

declare(strict_types=1);


namespace App\Models\Traits\Scope;

use App\Alonti\Cart\CartManager;

trait CustomScope
{
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeDisplayStatus($query)
    {
        return $query->where('display_status', 1);
    }

    public function scopeParent($query)
    {
        return $query->where('parent_id', null);
    }

    public function scopeWarmCookieCategory($query)
    {
        $query->where('name', 'like', '%cookie%');
    }

    public function scopeAvailableInStore($query)
    {
        $deliveryAreaInfo = session()->has('UserDeliveryInformation') ? session()->get('UserDeliveryInformation') : [];
        $cartInfo = app(CartManager::class)->getActiveCart();

        if ($cartInfo) {
            $cafeId = $cartInfo->cafe_id;
        } else {
            $cafeId = isset($deliveryAreaInfo['alontiDeliveryArea']['cafe'])
                ? $deliveryAreaInfo['alontiDeliveryArea']['cafe']['id']
                : '';
        }

        if ($cafeId != '') {
            $query->where('available_all_store', 1)->orWhereHas('availableStore', function ($q) use ($cafeId) {
                $q->where('cafe_id', $cafeId);
            });
        } else {
            $query->where('available_all_store', 1);
        }
    }
}
