<?php

declare(strict_types=1);

namespace App\Alonti\Coupon;

use App\Alonti\Cart\CartManager;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Offmenu;
use App\Models\PromotionTypeProduct;

class UpdateCoupon
{
    public function calculateItemDiscount($cartItem)
    {
        $cart = Cart::find($cartItem->cart_id);
        if (!$cart->coupon->isOneFreeProduct()) {
            $items = CartItem::where('id', $cartItem->id)->get();
            $addonItems = CartItem::where('addon_cartitem_id', $cartItem->id)->get();
            $cartItems = $addonItems->merge($items);
            $productIds = [];
            foreach ($cartItems as $item) {
                $productIds[] = $item->product_id;
            }
            $isValid = app(CouponValidation::class)->validateCouponForProductAndVariant($cart->coupon, $cartItems);
            if ($isValid) {
                $isWarmCookie = CartManager::checkCartHasOnlyWarmCookie($cart);
                if ($isWarmCookie['giftToDisplay']) {
                    self::deleteItemDiscount($cartItem, $cartItem->addons);
                } else {
                    if ($cart->coupon->isFreePromoTypes()) {
                        self::saveFreeCartItem($cartItems, $cart, $productIds);
                    } else {
                        if ($cart->coupon->promotionType->applies_to == 'selection') {
                            app(CouponManager::class)->calculateSelectionDiscount($cart, $cart->coupon, $cart->items);
                        } else {
                            app(CouponManager::class)->getDiscount($cart->coupon, $cart, $productIds);
                        }
                    }
                }
            }
        }
    }

    public function updateItemDiscount($cartItem)
    {
        $cart = Cart::find($cartItem->cart_id);
        if ($cart->coupon) {
            if ($cart->coupon->isFreePromoTypes()) {
                self::updateFreeCartItem($cartItem, $cart->coupon);
            } else {
                self::calculateDiscountAndUpdateCart($cart);
            }
        }
    }

    public function deleteItemDiscount($cartItem, $addons)
    {
        $cart = Cart::find($cartItem->cart_id);
        if ($cart->coupon) {
            $isWarmCookie = CartManager::checkCartHasOnlyWarmCookie($cart);
            if ($cart->items->isEmpty() || $isWarmCookie['giftToDisplay']) {
                $cart->deleteCartDiscount($cart->coupon);
            }

            if ($cart->coupon->isFreePromoTypes()) {
                self::deleteFreeCartItem($cartItem, $cart, $addons);
            } else {
                self::calculateDiscountAndUpdateCart($cart);
            }
        }
    }

    public function updateDiscountForZipcode($cart, $coupon)
    {
        $productIds = [];
        foreach ($cart->items as $item) {
            $productIds[] = $item->product_id;
        }
        $result = Coupon::getDetails($coupon->coupon, $cart, $productIds);
        if (!$result) {
            $cart->deleteCartDiscount($coupon);
        } elseif (!$coupon->isFreePromoTypes()) {
            app(CouponManager::class)->getDiscount($coupon, $cart, $productIds);
        }

        if ($coupon->isOneFreeProduct()) {
            $freeCartItem = CartItem::getFreeCartItemByCartId($cart->id);
            $discount = -1 * $freeCartItem->total;
            $freeCartItem->discount = $discount;
            $freeCartItem->save();
            $cart->updateCartDiscount();
        }
    }

    public function calculateDiscountAndUpdateCart($cart)
    {
        $discount = 0;
        // Added by suresh
        $minOrderValidation = false;
        $itemTotal = 0;
        foreach ($cart->items as $item) {
            $itemTotal += $item->total;
        }
        if ($cart->coupon->promotionType->all_orders == 1 && $cart->coupon->promotionType->min_order_value > 0) {
            $minOrderValidation = true;
            if ($itemTotal >= $cart->coupon->promotionType->min_order_value) {
                $discount = abs(($itemTotal * $cart->coupon->promotionType->discount_value) / 100);
            }
        }
        if (!$minOrderValidation) {
            if (
                $cart->coupon->promotionType->discount_type == 1 &&
                $cart->coupon->promotionType->spl_condition != null &&
                $cart->coupon->promotionType->applies_to != '' &&
                $cart->coupon->promotionType->special == 1
            ) {
                $discount = $this->getPercentageDiscountWithCond($cart->coupon, $cart->items);
            } else {
                foreach ($cart->items as $item) {
                    $discount += $item->quantity * $item->discount;
                }
            }
        }
        // Added by suresh
        if ($discount == 0) {
            $cart->deleteCartDiscount($cart->coupon);
        } else {
            $cart->updateDiscount($cart->coupon, $discount);
        }
    }

    public function updateFreeCartItem($cartItem, $coupon)
    {
        $freeCartItem = CartItem::getFreeCartItem($cartItem->id);
        if ($freeCartItem && !$coupon->isOneFreeProduct()) {
            CartItem::updateDiscount($freeCartItem->id, ['quantity' => $cartItem->quantity]);
        }
    }

    public function deleteFreeCartItem($cartItem, $cart, $addons)
    {
        if ($cart->coupon->isOneFreeProduct()) {
            $cartItems = CartItem::getCartItemsCartId($cart->id);
            if ($cartItems->isNotEmpty()) {
                return;
            }
        }

        CartItem::deleteFreeCartItem($cartItem, $cart->coupon);
        if ($addons->isNotEmpty()) {
            foreach ($addons as $addon) {
                CartItem::deleteFreeCartItem($addon, $cart->coupon);
            }
        }

        $freeCartItems = CartItem::getFreeCartItemsByCartId($cart->id);
        if ($freeCartItems->isEmpty()) {
            $cart->deleteCartDiscount($cart->coupon);
        }
    }

    public function saveFreeCartItem($cartItems, $cart, $productIds)
    {
        $freeCartItem = CartItem::getFreeCartItemByCartId($cart->id);
        if ($freeCartItem) {
            $data = $freeCartItem->toArray();
            $data['cartOptions'] = $data['options'];
            unset($data['options']);
            unset($data['id']);
            unset($data['free_item_id']);
            app(CouponManager::class)->getFreeProductDiscount($cartItems, $data, $cart->coupon, $productIds);
        }
    }

    public function updateOffmenuDiscount($cartId)
    {
        $cart = Cart::find($cartId);
        if ($cart->order) {
            Offmenu::updateOffmenu($cart, $cart->order_id);
        }
    }

    public function getPercentageDiscountWithCond($coupon, $cartItems)
    {
        $promoProducts = $this->fetchPromoProducts($coupon->promotionType->id);
        $category = new Category();
        $checkPromotionCondProd = $category->getCategoryWithProducts($coupon->promotionType->spl_condition);
        $discount_total = 0;
        if (!empty($checkPromotionCondProd)) {
            $invoiceHasTheItems = false;
            foreach ($cartItems as $item) {
                if (in_array($item->product_id, $checkPromotionCondProd)) {
                    $invoiceHasTheItems = true;
                    break;
                }
            }
            foreach ($cartItems as $item) {
                if (in_array($item->product_id, $promoProducts)) {
                    $itemPrice = $item->total / $item->quantity;
                    if ($invoiceHasTheItems) {
                        $discount = -1 * abs(($itemPrice * $coupon->promotionType->discount_value) / 100);
                    } else {
                        $discount = 0;
                    }
                    CartItem::updateDiscount($item->id, ['discount' => $discount]);
                    $discount_total += $item->quantity * $discount;
                }
            }
        }

        return $discount_total;
    }

    public function getPromotypeProducts($coupon, $productIds)
    {
        $promoProducts = [];
        if ($coupon->promotionType->all_orders == 1) {
            $promoProducts = $productIds;
        }
        if ($coupon->promotionType->promotionTypeProduct->isNotEmpty()) {
            $promoProducts = [];
            foreach ($coupon->promotionType->promotionTypeProduct as $product) {
                $promoProducts[] = $product->product_id;
            }
        }

        return $promoProducts;
    }

    public function fetchPromoProducts($promoId)
    {
        $promoTypeProducts = PromotionTypeProduct::where(['promotion_type_id' => $promoId])->get();
        $product_id = [];
        foreach ($promoTypeProducts as $ptproduct) {
            $product_id[] = $ptproduct->product_id;
        }

        return $product_id;
    }
}
