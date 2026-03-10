<?php

declare(strict_types=1);

namespace App\Alonti\Coupon;

use App\Alonti\Cart\CartManager;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartOption;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Offmenu;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromotionTypeProduct;
use App\Models\StatePrice;

class CouponManager
{
    public $cart;

    public $freePromotypes;

    protected $discountFreeCategoryIds = null;

    public function __construct()
    {
        $this->cart = app(CartManager::class)->getActiveCart();
        $this->freePromotypes = [28, 30, 31, 35, 36];
    }

    public function applyCoupon($couponCode)
    {
        if (!$this->cart) {
            $message = 'Cart not found!';

            return app(CouponResponse::class)->getFailureResponse($message);
        }

        // If all items are discount-free, block coupon entirely
        $items = $this->cart->items;

        $items = $this->cart->items;

        if ($items->isEmpty()) {
            $message = 'Your cart is empty';

            return app(CouponResponse::class)->getFailureResponse($message);
        }

        $hasDiscountEligibleItem = false;
        foreach ($items as $item) {
            if (!$this->isDiscountFreeCartItem($item)) {
                $hasDiscountEligibleItem = true;
                break;
            }
        }

        if (!$hasDiscountEligibleItem) {
            $message = 'This coupon cannot be applied because none of the cart items are eligible for discounts.';

            return app(CouponResponse::class)->getFailureResponse($message);
        }

        $productIds = [];
        foreach ($this->cart->items as $item) {
            $productIds[] = $item->product_id;
        }

        $coupon = Coupon::getDetails($couponCode, $this->cart, $productIds);
        $isValid = app(CouponValidation::class)->validateCoupon($coupon, $this->cart);

        if (!$isValid) {
            $message = 'Invalid Coupon! Please enter a valid coupon';

            return app(CouponResponse::class)->getFailureResponse($message);
        }

        $cart = $this->cart;
        $items = $this->cart->items;
        $itemTotal = $items->sum('total');

        // get the offmenu items
        $servingWareOptions = Offmenu::where('cart_id', $cart->id)->where('flag', 6)->get();

        if ($servingWareOptions) {
            $servingWareTotal = $servingWareOptions->sum('price');
            $itemTotal = $itemTotal + $servingWareTotal;
        }

        if (
            !empty($coupon->promotionType) &&
            $coupon->promotionType->all_orders &&
            $coupon->promotionType->discount_type == 4 &&
            $coupon->promotionType->day_type == 3 &&
            $coupon->promotionType->notes == 'one time use for the year'
        ) {
            if (!$cart->order_id) {
                $currentYearOrders = app(Order::class)
                    ->where('alonti_user_id', '=', $cart->user_id)
                    ->whereYear('d_date', '=', date('Y'))
                    ->where('status', '!=', 'canceled')
                    ->with([
                        'offmenus' => function ($q) {
                            return $q->where('flag', '=', 5)->whereNotNull('coupon_id');
                        },
                    ])
                    ->get();
            } else {
                $currentYearOrders = app(Order::class)
                    ->where('alonti_user_id', '=', $cart->user_id)
                    ->where('id', '!=', $cart->order_id)
                    ->whereYear('d_date', '=', date('Y'))
                    ->where('status', '!=', 'canceled')
                    ->with([
                        'offmenus' => function ($q) {
                            return $q->where('flag', '=', 5)->whereNotNull('coupon_id');
                        },
                    ])
                    ->get();
            }

            $applyCoupon = false;

            if (empty($currentYearOrders)) {
                $applyCoupon = true;
            } else {
                $couponApplied = false;
                foreach ($currentYearOrders as $key => $value) {
                    if ($value->offmenus->count() > 0) {
                        if ($value->offmenus[0]->coupon_id == $coupon->id) {
                            $couponApplied = true;
                        }
                    }
                }

                if (!$couponApplied) {
                    $applyCoupon = true;
                }
            }

            if (!$applyCoupon) {
                $message = 'Coupon code can only be used one time.';

                return app(CouponResponse::class)->getFailureResponse($message);
            } else {
                $data = $this->applyCouponForFirstOrderOfTheYear($coupon, $cart);
            }
        } elseif ($coupon->promotionType->all_orders == 1 && $coupon->promotionType->min_order_value > 0) {
            if ($itemTotal >= $coupon->promotionType->min_order_value) {
                $data = $this->getDiscount($coupon, $cart, $productIds);
            } else {
                $message =
                    'Coupon will be applied only the cart subtotal is more than or equal to $' .
                    abs($coupon->promotionType->min_order_value);

                return app(CouponResponse::class)->getFailureResponse($message);
            }
        } elseif ($coupon->promotionType->applies_to == 'selection') {
            $data = $this->calculateSelectionDiscount($cart, $coupon, $items);
        } else {
            $data = $this->getDiscount($coupon, $cart, $productIds);
        }

        $message = 'Coupon has been applied successfully';

        return app(CouponResponse::class)->getSuccessResponse($message, $data);
    }

    public function applyCouponForFirstOrderOfTheYear($coupon, $cart)
    {
        $result = [];
        $discount_total = 0;

        foreach ($cart->items as $item) {
            // Skip items in discount-free categories
            if ($this->isDiscountFreeCartItem($item)) {
                continue;
            }

            $itemPrice = $item->total / $item->quantity;
            $discount = -1 * abs(($itemPrice * $coupon->promotionType->discount_value) / 100);
            CartItem::updateDiscount($item->id, ['discount' => $discount]);
            $discount_total += $item->quantity * $discount;
        }

        $cart->updateDiscount($coupon, $discount_total);
        $result['discount'] = round($discount, 2);
        $result['promotion_type'] = $coupon->promotionType->name;
        $result['cart'] = $this->getCartInfo($cart);

        return $result;
    }

    public function getDiscount($coupon, $cart, $productIds)
    {
        $result = [];
        $result['free'] = false;
        $promoProducts = $this->getPromotypeProducts($coupon, $productIds);

        if ($coupon->isFreePromoTypes()) {
            $result['free'] = true;
            $result['products'] = Product::getFreeProducts($coupon->promotion_type_id);
            $result['coupon_id'] = $coupon->id;
        } else {
            $discount = $this->calculateDiscount($coupon, $cart->items, $promoProducts);
            $cart->updateDiscount($coupon, $discount);
            $result['discount'] = round($discount, 2);
            $result['promotion_type'] = $coupon->promotionType->name;
            $result['cart'] = $this->getCartInfo($cart);
        }

        return $result;
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

    public function calculateDiscount($coupon, $cartItems, $promoProducts)
    {
        $discount = '';
        $promoVariants = app(CouponValidation::class)->getPromoTypeProductVariants($coupon);
        if (
            $coupon->isPercentageDiscount() &&
            $coupon->promotionType->spl_condition != null &&
            $coupon->promotionType->applies_to != '' &&
            $coupon->promotionType->special == 1
        ) {
            $discount = $this->getPercentageDiscountWithCond($coupon, $cartItems);
        } elseif ($coupon->isPercentageDiscount()) {
            $discount = $this->getPercentageDiscount($coupon, $cartItems, $promoProducts, $promoVariants);
        }
        if ($coupon->isPriceDiscount()) {
            $discount = $this->getPriceDiscount($coupon, $cartItems, $promoProducts, $promoVariants);
        }
        if ($coupon->isFreeDiscount()) {
            $discount = $this->getFreeDiscount($coupon, $cartItems, $promoProducts, $promoVariants);
        }

        return $discount;
    }

    public function getPercentageDiscount($coupon, $cartItems, $promoProducts, $promoVariants)
    {
        $discount_total = 0;

        foreach ($cartItems as $item) {
            // ignore discount-free category items
            if ($this->isDiscountFreeCartItem($item)) {
                continue;
            }

            $isVariant = app(CouponValidation::class)->validatePromotypeForVariant($promoVariants, $item);

            if (in_array($item->product_id, $promoProducts) && $isVariant) {
                $itemPrice = $item->total / $item->quantity;
                $discount = -1 * abs(($itemPrice * $coupon->promotionType->discount_value) / 100);
                CartItem::updateDiscount($item->id, ['discount' => $discount]);
                $discount_total += $item->quantity * $discount;
            }
        }

        return $discount_total;
    }

    public function getPriceDiscount($coupon, $cartItems, $promoProducts, $promoVariants)
    {
        $discount_total = 0;

        foreach ($cartItems as $item) {
            // Ignore discount-free category items
            if ($this->isDiscountFreeCartItem($item)) {
                continue;
            }

            $isVariant = app(CouponValidation::class)->validatePromotypeForVariant($promoVariants, $item);

            if (in_array($item->product_id, $promoProducts) && $isVariant) {
                $price = $coupon->promotionType->discount_value;
                $promoProduct = PromotionTypeProduct::getPromoProduct($coupon->promotion_type_id, $item->product_id);

                if ($promoProduct) {
                    $price = $promoProduct->price;
                }

                $item_price = $item->total / $item->quantity;
                $discount = -1 * abs($item_price - $price);

                if ($coupon->isPricePromoType()) {
                    $discount = -1 * abs($price);

                    if (
                        $item->product &&
                        $item->product->minimum_serve > 1 &&
                        !$item->product->apply_discount_per_unit
                    ) {
                        $discount = -1 * (abs($price) / abs($item->product->minimum_serve));
                    }
                }

                CartItem::updateDiscount($item->id, ['discount' => $discount]);
                $discount_total += $item->quantity * $discount;
            }
        }

        return $discount_total;
    }

    public function getFreeDiscount($coupon, $cartItems, $promoProducts, $promoVariants)
    {
        $discount_total = 0;

        if ($coupon->isFreeDiscountPromoTypes()) {
            $discount_total = -1 * ($order->delivery + $order->salestax);
        } else {
            if (
                $coupon->promotionType->free_delivery &&
                $coupon->promotionType->notes == 'Free Delivery after $100 food spending'
            ) {
                $cartItemTotal = $cartItems->sum('total');

                if ($cartItemTotal >= $coupon->promotionType->min_order_value) {
                    foreach ($cartItems as $item) {
                        // Do not mark discount-free items as free delivery via coupon
                        if ($this->isDiscountFreeCartItem($item)) {
                            continue;
                        }

                        CartItem::updateDiscount($item->id, ['free_delivery' => 1]);
                    }
                }
            } else {
                foreach ($cartItems as $item) {
                    // Ignore discount-free items here too
                    if ($this->isDiscountFreeCartItem($item)) {
                        continue;
                    }

                    $isVariant = app(CouponValidation::class)->validatePromotypeForVariant($promoVariants, $item);

                    if (in_array($item->product_id, $promoProducts) && $isVariant) {
                        $free_delivery = 0;

                        if ($coupon->isFreeDiscountPromoTypes()) {
                            $free_delivery = 1;
                        }

                        CartItem::updateDiscount($item->id, ['free_delivery' => $free_delivery]);
                    }
                }
            }
        }

        return $discount_total;
    }

    public function addFreeItem($data)
    {
        $coupon = Coupon::find($data['coupon_id']);
        unset($data['coupon_id']);
        if ($this->cart) {
            $productId = [];
            foreach ($this->cart->items as $item) {
                $productIds[] = $item->product_id;
            }
            $discount = $this->getFreeProductDiscount($this->cart->items, $data, $coupon, $productIds);
            $cart = Cart::find($this->cart->id);
            $cart->updateDiscount($coupon, $discount);
            $result['discount'] = $discount;
            $result['promotion_type'] = $coupon->promotionType->name;
            $result['cart'] = $this->getCartInfo($cart);
            $message = 'Free product is added to cart successfully';

            return app(CouponResponse::class)->getSuccessResponse($message, $result);
        }
    }

    public function getFreeProductDiscount($items, $data, $coupon, $productIds)
    {
        $promoProducts = $this->getPromotypeProducts($coupon, $productIds);
        $promoVariants = app(CouponValidation::class)->getPromoTypeProductVariants($coupon);
        foreach ($items as $item) {
            $freeCartItem = CartItem::getFreeCartItem($item->id);
            if (!$freeCartItem) {
                $isVariant = app(CouponValidation::class)->validatePromotypeForVariant($promoVariants, $item);
                if (in_array($item->product_id, $promoProducts) && $isVariant) {
                    $discount = $this->saveFreeProductDiscount($item, $data, $coupon);
                    if ($coupon->isOneFreeProduct()) {
                        return $discount;
                        break;
                    }
                }
            }
        }
    }

    public function saveFreeProductDiscount($item, $data, $coupon)
    {
        $qty = $item->quantity;
        $price = 0;
        if ($coupon->isOneFreeProduct()) {
            $statePrice = StatePrice::getPrice(
                $data['product_variant_id'],
                ProductVariant::ENTITY_TYPE,
                $this->cart->state_id
            );
            $qty = 1;
            $price = $statePrice->price;
        }
        $discount = -1 * abs($price);
        $data['price'] = $price;
        $data['quantity'] = $qty;
        $data['discount'] = $discount;
        if (!$coupon->isOneFreeProduct()) {
            $data['free_item_id'] = $item->id;
        }
        $data['is_free_item'] = 1;

        $cartItem = new CartItem();
        $cartItem = CartManager::saveCartItem($data, $this->cart, $cartItem);
        if ($cartItem) {
            CartManager::createCartItemOptions($cartItem, $this->cart->state_id, $data['cartOptions']);
            $discount = -1 * abs($cartItem->total / $cartItem->quantity);
            CartItem::updateDiscount($cartItem->id, ['discount' => $discount]);
        }
        $cart = Cart::find($this->cart->id);
        $cart->calculateAndUpdate();

        return $discount;
    }

    public function getCartInfo($cart)
    {
        $this->cart = $cart->fresh();

        return [
            'taxable' => $this->cart->taxable,
            'nontaxable' => $this->cart->nontaxable,
            'delivery' => $this->cart->delivery_fee,
            'salestax' => $this->cart->sales_tax,
            'subtotal' => $this->cart->subtotal,
            'gratuity' => $this->cart->gratuity,
            'total' => $this->cart->total,
            'discount' => $this->cart->discount,
            'count' => $this->cart->getCartCount(),
        ];
    }

    public function calculateOrderMinValue($cart, $items, $coupon)
    {
        $discount = 0;
        $total = $items->sum('total');
        if ($total >= $coupon->promotionType->min_order_value) {
            $discount = abs(($total * $coupon->promotionType->discount_value) / 100);
        }
        $cart->updateDiscount($coupon, $discount);
        $data['free'] = false;
        $data['discount'] = round($discount, 2);
        $data['promotion_type'] = $coupon->promotionType->name;
        $cart->fresh();
        $data['cart'] = $this->getCartInfo($cart);

        return $data;
    }

    public function calculateSelectionDiscount($cart, $coupon, $items)
    {
        $productIds = $coupon->promotionType->promotionTypeProduct
            ->map(function ($item, $key) {
                return $item->product_id;
            })
            ->toArray();

        $variantIds = $coupon->promotionType->promotionTypeProduct
            ->map(function ($item, $key) {
                return $item->product_variant_id;
            })
            ->toArray();

        $optionIds = [];
        $selectionIds = [];

        foreach ($coupon->promotionType->promotionTypeProduct as $key => $value) {
            foreach ($value->productSelections as $sel) {
                $optionIds[] = $sel->option_id;
                $selectionIds[] = $sel->selection_id;
            }
        }

        $cartSelectionIds = [];
        $selectionDiscount = 0;
        $selectionProductDiscount = [];

        if (!empty($productIds) && !empty($variantIds) && !empty($optionIds) && !empty($selectionIds)) {
            foreach ($items as $item) {
                // Options/modifiers of discount-free items must ignore coupons
                if ($this->isDiscountFreeCartItem($item)) {
                    continue;
                }

                if (in_array($item->product_id, $productIds) && in_array($item->product_variant_id, $variantIds)) {
                    $selectionProductDiscount[$item->id] = 0;
                    foreach ($item->options as $opt) {
                        if (
                            in_array($opt->product_option_id, $optionIds) &&
                            in_array($opt->product_selection_id, $selectionIds)
                        ) {
                            $cartSelectionIds[] = $opt->id;
                            $selectionDiscount += $item->quantity * $opt->unit_price;
                            $selectionProductDiscount[$item->id] += $opt->unit_price;
                        }
                    }
                }
            }
        }

        $discount = $selectionDiscount > 0 ? -1 * round($selectionDiscount, 2) : $selectionDiscount;

        if (!empty($cartSelectionIds)) {
            $updateOption = CartOption::whereIn('id', $cartSelectionIds)->update(['is_free' => 1]);
        }

        if (!empty($selectionProductDiscount)) {
            foreach ($selectionProductDiscount as $key => $value) {
                $itemDiscount = -1 * round($value, 2);
                $updateItem = CartItem::where('id', $key)->update(['discount' => $itemDiscount]);
            }
        }

        $cart->updateDiscount($coupon, $discount);
        $data['free'] = false;
        $data['discount'] = $discount;
        $data['promotion_type'] = $coupon->promotionType->name;
        $data['cart'] = $this->getCartInfo($cart);

        return $data;
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
                    // Don’t apply coupon discount on discount-free category items
                    if ($this->isDiscountFreeCartItem($item)) {
                        continue;
                    }

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

    public function fetchPromoProducts($promoId)
    {
        $promoTypeProducts = PromotionTypeProduct::where(['promotion_type_id' => $promoId])->get();
        $product_id = [];
        foreach ($promoTypeProducts as $ptproduct) {
            $product_id[] = $ptproduct->product_id;
        }

        return $product_id;
    }

    public function getDiscountFreeCategoryIds(): array
    {
        if ($this->discountFreeCategoryIds !== null) {
            return $this->discountFreeCategoryIds;
        }

        $this->discountFreeCategoryIds = Category::where('is_discount_free', 1)->pluck('id')->toArray();

        return $this->discountFreeCategoryIds;
    }

    public function isDiscountFreeCartItem($item): bool
    {
        // Ensure product relation is available
        if (!$item->relationLoaded('product')) {
            $item->load('product');
        }

        $categoryId = $item->product->category_id ?? null;
        if (!$categoryId) {
            return false;
        }

        return in_array($categoryId, $this->getDiscountFreeCategoryIds(), true);
    }
}
