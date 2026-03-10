<?php

declare(strict_types=1);


namespace App\Models;

use App\Alonti\Coupon\UpdateCoupon;
use App\Alonti\Order\OrderPlacement;
use App\Alonti\Support\EncryptIdentity;
use App\Mailer\CartMailer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Cart Model
 *
 * Represents a shopping cart with complex business logic for:
 * - Tax and fee calculations
 * - Group order management
 * - Coupon and discount handling
 * - State-based pricing updates
 * - Delivery fee calculations
 * - Individual vs group order differentiation
 */
class Cart extends BaseModel
{
    use EncryptIdentity;

    protected $table = 'oj_carts';

    protected static $unguarded = true;

    /**
     * Scope to eager load all related models for cart display
     *
     * Loads items, categories, products, variants, images, options, shipping, and billing
     * to reduce database queries when displaying cart contents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRelatedModels($query)
    {
        $query->with([
            'items',
            'items.category',
            'items.product',
            'items.product.image',
            'items.variant',
            'items.variant.image',
            'items.options',
            'items.options.option',
            'shipping',
            'billing',
        ]);
    }

    /**
     * Calculate and update cart totals with complex business logic
     *
     * Performs comprehensive cart calculations including:
     * - Taxable vs non-taxable items based on GL codes
     * - Delivery fee calculations with exceptions
     * - Sales tax calculations by location
     * - Gratuity calculations
     * - Amazon rewards application
     * - Serving ware option pricing
     *
     * @return bool Success status
     */
    public function calculateAndUpdate()
    {
        // Prevent modifications to completed orders
        $cart = $this->find($this->id);
        $amazonRewardsApplied = $cart->amazon_reward_applied;
        if ($cart && $cart->order && in_array($cart->order->status, ['Delivered', 'Canceled'])) {
            return false;
        } else {
            // Get cafe information from session or cart relationship
            if (isset(session()->get('UserDeliveryInformation')['alontiDeliveryArea'])) {
                $cafeInfo = session()->get('UserDeliveryInformation')['alontiDeliveryArea']['cafe'];
            } else {
                $cafeInfo = $cart->cafe;
            }

            // Initialize calculation variables
            $cafeGlCode = $cafeInfo->gl_code_id;
            $taxable = 0;
            $nontaxable = 0;
            $salesTax = 0;
            $deliveryFee = 0;
            $taxExempt = 0;
            $discount = 0;
            $glCodes = [03, 13]; // Special GL codes for tax calculations

            // Calculate taxable and non-taxable amounts based on GL codes and variants
            $hasCafeGlCode = in_array($cafeGlCode, $glCodes);
            $variant_ids = $cart->getExceptionalProductVariants(); // Get variants with special tax treatment

            $cart->items->each(function ($item) use (
                &$taxable,
                &$nontaxable,
                &$discount,
                $hasCafeGlCode,
                $variant_ids
            ) {
                // Skip items from invitees who haven't completed their order
                if ($item->cartInvitee() && $item->cartInvitee()->response != 4) {
                    return true;
                }

                // Determine tax treatment based on variant and GL code
                if ($variant_ids->isNotEmpty()) {
                    if ($variant_ids->contains($item->product_variant_id) && !$hasCafeGlCode) {
                        $nontaxable += $item->total;
                    } else {
                        $taxable += $item->total;
                    }
                } else {
                    $taxable += $item->total;
                }

                // Accumulate item discounts
                $discount += $item->quantity * $item->discount;
            });

            // Add discounts back to taxable amount for proper tax calculation
            $taxable += $discount;

            // Get shipping information to determine delivery vs pickup
            $shipping = Shipping::where('cart_id', $cart->id)->select('id', 'cart_id', 'shipping_type')->first();

            if ($taxable > 0 || $nontaxable > 0) {
                // No delivery fee for pickup orders
                if ($shipping && $shipping->shipping_type == 2) {
                    $deliveryFee = 0;
                } else {
                    // Calculate delivery fee based on category exceptions
                    $deliveryExceptionCategories = Category::where('delivery_exception', 1)->pluck('id');
                    $cartItemCategoryIds = $cart->items->pluck('category_id');
                    $regularDeliveryFee = true;

                    $cartItemCategoryIds->each(function ($categoryId) use (
                        $deliveryExceptionCategories,
                        &$regularDeliveryFee
                    ) {
                        if ($deliveryExceptionCategories->contains($categoryId)) {
                            $regularDeliveryFee = false;
                        } else {
                            $regularDeliveryFee = true;

                            return false;
                        }
                    });

                    // Use special delivery fee for exception categories (e.g., warm cookies)
                    if (!$regularDeliveryFee) {
                        $stateId = $cart->state_id;
                        $wcDeliveryFeeByState = $cart->deliveryFeeExistForTheState($stateId);
                        $deliveryFee = $wcDeliveryFeeByState->field_value;
                    } else {
                        // Calculate regular delivery fee with free delivery items
                        $freeDelivery = 0;
                        foreach ($cart->items as $val) {
                            if ($val->free_delivery) {
                                $freeDelivery += $val->total;
                            }
                        }

                        $total = $taxable + $nontaxable - $freeDelivery;

                        // Apply delivery fee rules: $10 flat fee under $100, 10% over $100
                        if ($total < 100) {
                            $deliveryFee = $freeDelivery > 0 ? 0 : 10;
                        } else {
                            $deliveryFee = round($total * 0.1, 2);
                        }
                    }
                }

                // Calculate sales tax
                if (Auth::user()) {
                    $taxExempt = Auth::user()->txexempt;
                }

                // Serving ware options must be use to calculate sales tax
                // Start: Only for serving ware option
                $offMenu = Offmenu::where('cart_id', $cart->id)->where('flag', 6)->get();

                if ($offMenu) {
                    foreach ($offMenu as $item) {
                        $taxable += $item->qty * $item->price;
                    }
                }
                // End: Only for serving ware option

                if (!$taxExempt) {
                    if ($cafeInfo) {
                        $cafeTaxDelivery = $cafeInfo->txdelivery;
                        $cafeNum = $cafeInfo->cafenum;
                        if ($cafeTaxDelivery == 'N') {
                            $deliveryTaxFee = 0;
                        } else {
                            $deliveryTaxFee = $deliveryFee;
                        }
                        $taxRate = $cart->getTaxRateForTheZipcodeAndCafe($cart->zipcode, $cafeNum);

                        // Log the tax rate, taxable, nontaxable, delivery fee, delivery tax fee
                        Log::info('Nontaxable: ' . $nontaxable);
                        Log::info('Delivery Fee: ' . $deliveryFee);
                        Log::info('Taxable: ' . $taxable);
                        Log::info('Delivery Tax Fee: ' . $deliveryTaxFee);
                        Log::info('Tax Rate: ' . $taxRate);

                        if (!empty($taxRate)) {
                            $salesTax = ($taxable + $deliveryTaxFee) * $taxRate;
                        }
                    }
                }
            }

            // Assign all the calculated values and store
            $cart->taxable = $taxable;
            $cart->nontaxable = $nontaxable;
            $cart->discount = $discount;
            $cart->subtotal = $taxable + $nontaxable;
            $cart->delivery_fee = $deliveryFee;
            $cart->sales_tax = $salesTax;
            $total = $taxable + $nontaxable + $deliveryFee + $salesTax;

            if ($total > 0) {
                if ($cart->billing != null || (!is_null($cart->gratuity_percentage) || !is_null($cart->gratuity))) {
                    if (is_null($cart->gratuity_percentage) && $cart->gratuity == 0) {
                        $cart->gratuity_percentage = null;
                        $cart->gratuity = 0;
                    } elseif ($cart->gratuity_percentage == 0 && is_null($cart->gratuity)) {
                        $cart->gratuity_percentage = 0;
                        $cart->gratuity = null;
                    } elseif ($cart->gratuity_percentage > 0) {
                        $cart->gratuity_percentage = $cart->gratuity_percentage;
                        $cart->gratuity = round(($total * $cart->gratuity_percentage) / 100, 2);
                    }
                    $cart->total = $total + $cart->gratuity;
                } else {
                    $cart->total = $total;
                }
                // reduce amazon rewards applied at the checkout
                $cart->total = $cart->total - $amazonRewardsApplied;
            } else {
                $cart->gratuity_percentage = null;
                $cart->gratuity = null;
                $cart->total = 0;
            }

            $cart->save();

            if ($cart->order) {
                $data['cartId'] = $cart->id;
                $orderPlacement = new OrderPlacement($data);
                $orderPlacement->frequentUpdateOrder();
            }
        }

        return true;
    }

    /**
     * Get product variants with special tax treatment
     *
     * Retrieves variants that have tax exceptions based on ZIP code prefix.
     * Used to determine which items should be non-taxable.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getExceptionalProductVariants()
    {
        // Use first 3 digits of ZIP code for exception lookup
        $zip = substr($this->zipcode, 0, 3);

        return Exception::where('flag', 1)
            ->where('zipcode', 'like', '%' . $zip . '%')
            ->whereNotNull('product_variant_id')
            ->pluck('product_variant_id');
    }

    public function deliveryFeeExistForTheState($stateId)
    {
        return Configuration::where([
            'column_key' => 'state_id',
            'field_key' => 'delivery_fee',
            'column_value' => $stateId,
        ])->first();
    }

    /**
     * Calculate total amount for a specific group order invitee
     *
     * Calculates the total cost of items ordered by a specific invitee in a group order.
     * Optionally excludes a specific item (useful for budget calculations during editing).
     *
     * @param  int  $invitee  Invitee ID
     * @param  int|null  $itemId  Optional item ID to exclude from total
     * @return float Total amount for the invitee
     */
    public function totalForInvitee($invitee, $itemId = null)
    {
        // Get items for specific invitee, optionally excluding an item
        if ($itemId) {
            $invitee_items = $this->items()
                ->where([
                    'id' => $itemId,
                ])
                ->where('invitee_id', '=', $invitee)
                ->where('addon_cartitem_id', '!=', $itemId)
                ->get();
        } else {
            $invitee_items = $this->items()->where('invitee_id', $invitee)->get();
        }

        return $invitee_items->sum('total');
    }

    public function otherItemTotalForInvitee($invitee, $itemId)
    {
        $invitee_items = $this->items()->where('id', '!=', $itemId)->where('invitee_id', $invitee)->get();

        return $invitee_items->sum('total');
    }

    public function getTaxRateForTheZipcodeAndCafe($zipcode, $cafeNum)
    {
        return Zipcode::where([
            'zipcode' => $zipcode,
            'cafe_id' => $cafeNum,
            'status' => 1,
        ])
            ->pluck('rate')
            ->first();
    }

    public function updateDeliveryArea($zipcode, $stateId, $cafeId)
    {
        if ($stateId == '' || !is_numeric($stateId)) {
            return true;
        }
        $this->cafe_id = $cafeId;
        $this->state_id = $stateId;
        $this->zipcode = $zipcode;
        $this->save();
        $this->items->fresh();
        if ($this->items->count() > 0) {
            $this->items->each(function (CartItem $cartItem) use ($stateId) {
                if (!$cartItem->free_item_id) {
                    $cartItem->updateItemPrice($stateId);
                }
            });
            if ($this->coupon) {
                app(UpdateCoupon::class)->updateDiscountForZipcode($this, $this->coupon);
            }
            $this->calculateAndUpdate();
        }

        return true;
    }

    /**
     * Check if cart is for a group order
     *
     * Determines whether this cart represents a group order or individual order.
     *
     * @return bool True if group order, false if individual
     */
    public function isGroupOrder()
    {
        return $this->group_order_id ? true : false;
    }

    /**
     * Scope for pending carts (not yet converted to orders)
     *
     * Filters carts that haven't been placed as orders yet.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->whereNull('order_id');
    }

    public function scopeMine($query)
    {
        return $query->where('user_id', auth()->user()->id);
    }

    public function scopeIndividual($query)
    {
        return $query->whereNull('group_order_id');
    }

    public function getCartCount()
    {
        // return CartItem::where(['cart_id' =>  $this->id, 'addon_cartitem_id' => NULL])->count('id');
        $moreServeProduct = Product::where('minimum_serve', 10)->pluck('id')->toArray();
        $itemCount = 0;
        if ($this->isGroupOrder() && !config('app.request-from-invitee')) {
            $completedInviteeId = $this->invitees()->where('response', 4)->pluck('invitee_id')->toArray();
            $itemCount += $this->ownerCartItems->whereNotIn('product_id', $moreServeProduct)->sum('quantity');
            $itemCount += $this->ownerCartItems->whereIn('product_id', $moreServeProduct)->count();
            $itemCount += CartItem::whereIn('invitee_id', $completedInviteeId)
                ->where('cart_id', $this->id)
                ->whereIn('product_id', $moreServeProduct)
                ->count();
            $itemCount += CartItem::whereIn('invitee_id', $completedInviteeId)
                ->where('cart_id', $this->id)
                ->whereNotIn('product_id', $moreServeProduct)
                ->withoutAddons()
                ->sum('quantity');
        } else {
            $itemCount += CartItem::where('cart_id', $this->id)->whereIn('product_id', $moreServeProduct)->count();
            $itemCount += CartItem::where(['cart_id' => $this->id])
                ->whereNotIn('product_id', $moreServeProduct)
                ->withoutAddons()
                ->sum('quantity');
        }

        return round($itemCount);
    }

    public function updateDiscount($coupon, $discount)
    {
        $this->discount = $discount;
        $this->promotion_type_id = $coupon->promotion_type_id;
        $this->coupon_id = $coupon->id;
        $this->save();
        $this->updateCartDiscount();
    }

    public function updateCartDiscount()
    {
        $cart = Cart::find($this->id);
        $cart->calculateAndUpdate();
    }

    public function deleteCartDiscount($coupon)
    {
        $cart = Cart::where(['id' => $this->id, 'coupon_id' => $coupon->id])->first();
        if ($cart) {
            $cart->items->each(function (CartItem $cartItem) use ($coupon) {
                if ($coupon->isFreePromoTypes()) {
                    CartItem::deleteFreeCartItem($cartItem, $coupon);
                } else {
                    $cartItem->deleteItemDiscount();
                    if ($coupon->promotionType->applies_to == 'selection') {
                        $cartItem->options->each(function (CartOption $cartOption) {
                            $cartOption->updateSelectionFree();
                        });
                    }
                }
            });
            $cart->discount = null;
            $cart->promotion_type_id = null;
            $cart->coupon_id = null;
            $cart->save();
            $cart->updateCartDiscount();
        }
    }

    public function items()
    {
        $query = $this->hasMany(CartItem::class, 'cart_id');
        if ($this->isGroupOrder() && !config('app.request-from-invitee') && request()->segment(2) !== 'delete-cart') {
            $completedInviteeId = $this->invitees()->where('response', 4)->pluck('invitee_id')->toArray();
            $query->where(function ($q) use ($completedInviteeId) {
                $q->whereNull('invitee_id')->orWhereIn('invitee_id', $completedInviteeId);
            });
        }

        return $query;
    }

    public function invitees()
    {
        return $this->hasMany(CartInvitee::class, 'cart_id');
    }

    public function shipping()
    {
        return $this->hasOne(Shipping::class, 'cart_id');
    }

    public function billing()
    {
        return $this->hasOne(Billing::class, 'cart_id');
    }

    public function mailer()
    {
        return new CartMailer($this);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paymentProfile()
    {
        return $this->belongsTo(CimPaymentProfile::class, 'cim_payment_profile_id');
    }

    public function order_group()
    {
        return $this->belongsTo(GroupOrder::class, 'group_order_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function promotionType()
    {
        return $this->belongsTo(PromotionType::class, 'promotion_type_id');
    }

    // Create a offmenu relation
    public function offmenus()
    {
        return $this->hasMany(Offmenu::class, 'cart_id');
    }

    public function discardCart()
    {
        try {
            // Delete offmenu entries referencing this cart
            if ($this->offmenus) {
                $this->offmenus()->delete();
            }

            if ($this->items) {
                $this->items->each(function ($item) {
                    $item->deleteItem();
                });
            }

            if ($this->shipping) {
                $this->shipping->delete();
            }

            if ($this->billing) {
                $this->billing->delete();
            }

            $this->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Error discarding cart: ' . $e->getMessage(), [
                'cart_id' => $this->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function getOwnerCountAttribute()
    {
        $moreServeProduct = Product::where('minimum_serve', 10)->pluck('id')->toArray();
        $count = 0;
        $count += $this->items()
            ->withoutAddons()
            ->whereIn('product_id', $moreServeProduct)
            ->whereNull('invitee_id')
            ->count();
        $count += $this->items()
            ->whereNotIn('product_id', $moreServeProduct)
            ->withoutAddons()
            ->whereNull('invitee_id')
            ->sum('quantity');

        return $count;
    }

    public function ownerCartItems()
    {
        return $this->items()->withoutAddons()->whereNull('invitee_id');
    }

    public function responseCount($reponse = [])
    {
        $q = $this->invitees();
        if ($reponse) {
            $q = $q->whereIN('response', $reponse);
        }

        return $q->count();
    }

    public function inviteeNotCompleted($reponse = [])
    {
        $q = $this->invitees();
        if ($reponse) {
            $q = $q->whereIN('response', $reponse)->pluck('invitee_id');
        }

        return $q->toArray();
    }

    public function cartItemsAndOptionsCurrentStatus($items)
    {
        $allowProcess = true;
        $productStatus = $variantStatus = $packageStatus = $optionStatus = [];
        if ($items) {
            foreach ($items as $key => $value) {
                // dd($value->variant->packageSizes);
                if (!$value->product || !$value->variant) {
                    $productStatus[$value->id] = false;
                }
                if ($value->product && (!$value->product->status || $value->product->deleted_at)) {
                    $productStatus[$value->id] = false;
                }
                if ($value->variant && (!$value->variant->status || $value->variant->deleted_at)) {
                    $variantStatus[$value->id] = false;
                }
                if ($value->product_package_id && !$value->package) {
                    $packageStatus[$value->id] = false;
                }
                if ($value->product_package_id && $value->variant->packageSizes) {
                    $size = [];
                    foreach ($value->variant->packageSizes as $sizes) {
                        $size[] = $sizes->size;
                    }
                    if (!in_array($value->package_size, $size)) {
                        $packageStatus[$value->id] = false;
                    }
                }
                if ($value->options) {
                    foreach ($value->options as $key => $opt) {
                        $optionSelectionActive = ProductOptionSelection::where([
                            'product_option_id' => $opt->product_option_id,
                            'product_selection_id' => $opt->product_selection_id,
                        ])->first();
                        if (!$optionSelectionActive) {
                            $optionStatus[$opt->id] = false;
                        }
                        if (!$opt->option) {
                            $optionStatus[$opt->id] = false;
                        }
                        if (!$opt->selection) {
                            $optionStatus[$opt->id] = false;
                        }
                        if ($opt->option && (!$opt->option->status && $opt->option->deleted_at)) {
                            $optionStatus[$opt->id] = false;
                        }
                        if ($opt->selection && (!$opt->selection->status && $opt->selection->deleted_at)) {
                            $optionStatus[$opt->id] = false;
                        }
                    }
                }
            }
        }

        if (
            array_search(false, $productStatus) ||
            array_search(false, $variantStatus) ||
            array_search(false, $packageStatus) ||
            array_search(false, $optionStatus)
        ) {
            $allowProcess = false;
        }

        // dd($productStatus, $optionStatus, $allowProcess);
        return $allowProcess;
    }

    public function cafe()
    {
        return $this->belongsTo(Cafe::class, 'cafe_id');
    }

    public function reward()
    {
        return $this->hasOne(Reward::class, 'cart_id');
    }

    public function abandonedCart()
    {
        return $this->hasOne(AbandonedCart::class, 'cart_id');
    }

    public function groupOrderConfig()
    {
        return $this->hasOne(GroupOrderConfiguration::class, 'cart_id');
    }
}
