<?php

declare(strict_types=1);


namespace App\Models;

use App\Alonti\Cart\CartManager;
use App\Alonti\Coupon\UpdateCoupon;
use App\Alonti\Support\EncryptIdentity;

/**
 * Cart Item Model
 *
 * Represents individual items in a shopping cart with:
 * - Complex quantity and pricing calculations
 * - Option and add-on management
 * - Discount and coupon handling
 * - Box lunch functionality
 * - Free item management
 * - State-based pricing updates
 * - Group order invitee tracking
 */
class CartItem extends BaseModel
{
    use EncryptIdentity;

    protected $table = 'oj_cart_items';

    protected static $unguarded = true;

    /**
     * Scope to exclude add-on items
     *
     * Filters out items that are add-ons to other cart items,
     * showing only main cart items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutAddons($query)
    {
        $query->where(['addon_cartitem_id' => null]);
    }

    /**
     * Get edit URL for cart item
     *
     * Generates appropriate edit URL based on user context (invitee vs regular user).
     *
     * @return string Edit URL for the cart item
     */
    public function getEditUrlAttribute()
    {
        // Use invitation URL for group order invitees
        if (config()->get('app.request-from-invitee')) {
            return url('invitation/' . $this->product->uniqueurl->url . '?item_id=' . $this->encrypted_id);
        }

        return url($this->product->uniqueurl->url . '?item_id=' . $this->encrypted_id);
    }

    /**
     * Get total item price including add-ons
     *
     * Calculates complete item price including base item and all add-ons.
     *
     * @return float Total price with add-ons
     */
    public function getItemTotalAttribute()
    {
        $total = $this->total;
        $total += $this->addons->sum('total');

        return $total;
    }

    /**
     * Update cart item quantity with complex calculations
     *
     * Updates item quantity and recalculates:
     * - Option quantities based on minimum serve requirements
     * - Item and option totals
     * - Coupon discounts
     * - Cart totals
     *
     * @param  float  $quantity  New quantity
     * @return array Updated cart information including totals
     */
    public function updateQuantity(float $quantity)
    {
        // Update option quantities based on minimum serve requirements
        if ($this->options) {
            $minServe = $this->product->minimum_serve;
            // Prevent division by zero error
            if ($minServe > 0) {
                foreach ($this->options as $option) {
                    $optQty = $quantity / $minServe;
                    $option['quantity'] = $optQty;
                    $option->total = $option->unit_price * $optQty;
                    $option->save();
                }
            }
        }
        // Recalculate item total with updated option prices
        $this->options->fresh();
        $sumPriceOfOptions = $this->options->pluck('total')->sum();
        $itemTotal = round($quantity * ($this->unit_price + $this->package_price) + $sumPriceOfOptions, 2);
        $this->quantity = $quantity;
        $this->total = $itemTotal;
        $this->save();

        // Apply coupon discounts before cart recalculation
        app(UpdateCoupon::class)->updateItemDiscount($this);
        $this->cart->calculateAndUpdate();
        $this->cart = $this->cart->fresh();
        $cartInfo = [
            'taxable' => round($this->cart->taxable, 2),
            'nontaxable' => round($this->cart->nontaxable, 2),
            'discount' => round($this->cart->discount, 2),
            'delivery' => round($this->cart->delivery_fee, 2),
            'salestax' => round($this->cart->sales_tax, 2),
            'subtotal' => round($this->cart->subtotal, 2),
            'gratuity' => round($this->cart->gratuity, 2),
            'total' => round($this->cart->total, 2),
        ];
        $moreServeProduct = Product::where('minimum_serve', 10)->pluck('id')->toArray();
        $count = 0;
        $count += CartItem::where(['cart_id' => $this->cart->id])
            ->whereIn('product_id', $moreServeProduct)
            ->count();
        $count += CartItem::where(['cart_id' => $this->cart->id])
            ->whereNotIn('product_id', $moreServeProduct)
            ->withoutAddons()
            ->sum('quantity');
        $itemCount = round($count);
        $itemTotal += round($this->addons->sum('total'), 2);

        return ['item_count' => $itemCount, 'item_total' => $itemTotal, 'cart_info' => $cartInfo];
    }

    /**
     * Delete cart item and associated data
     *
     * Removes cart item along with:
     * - All add-on items
     * - All cart options
     * - Coupon discounts
     * Updates cart totals after deletion.
     *
     * @return array Updated cart information or empty array on failure
     */
    public function deleteItem()
    {
        // Delete associated add-on items first
        $cartAddonId = CartItem::where(['addon_cartitem_id' => $this->id])->pluck('id');
        if ($cartAddonId->count() > 0) {
            $this->deleteAddonItem($cartAddonId);
        }

        // Delete cart options
        if ($this->options->count() > 0) {
            $deletedOption = CartOption::where('cart_item_id', $this->id)->delete();
        } else {
            $deletedOption = true;
        }
        // Delete the cart item itself
        $deleteItem = $this->destroy($this->id);
        if ($deleteItem && $deletedOption) {
            // Remove coupon discounts associated with this item
            $cartItem = $this;
            $addons = $cartItem->addons;
            app(UpdateCoupon::class)->deleteItemDiscount($cartItem, $addons);

            // Recalculate cart totals
            $this->cart->calculateAndUpdate();
            $this->cart = $this->cart->fresh();
            $cartInfo = [
                'taxable' => round($this->cart->taxable, 2),
                'nontaxable' => round($this->cart->nontaxable, 2),
                'delivery' => round($this->cart->delivery_fee, 2),
                'salestax' => round($this->cart->sales_tax, 2),
                'subtotal' => round($this->cart->subtotal, 2),
                'total' => round($this->cart->total, 2),
            ];
            $itemCount = $this->where(['cart_id' => $this->cart->id])
                ->withoutAddons()
                ->count();

            return ['itemCount' => $itemCount, 'cart_info' => $cartInfo];
        } else {
            return [];
        }
    }

    public function deleteAddonItem($addonCartItemIds)
    {
        // $deletedOption = // Unused variable
        CartOption::whereIn('cart_item_id', $addonCartItemIds)->delete();
        // $deleteItem =  // Unused variable
        $this->destroy($addonCartItemIds);

        return true;
    }

    /**
     * Update item pricing based on state
     *
     * Updates prices for item, package, and options when delivery state changes.
     * Recalculates total with new state-specific pricing.
     *
     * @param  int  $stateId  New state ID for pricing
     * @return bool Success status
     */
    public function updateItemPrice($stateId)
    {
        // Update option prices for new state
        $this->options->each(function (CartOption $cartOption) use ($stateId) {
            $cartOption->updateOptionPrice($stateId);
        });

        // Get new variant price for state
        $variantPrice = StatePrice::where([
            'entity_id' => $this->product_variant_id,
            'entity_type' => config('custom.entitytype.package'),
            'state_id' => $stateId,
        ])->first();

        // Update package price if applicable
        if ($this->product_package_id != '') {
            $packagePrice = StatePrice::where([
                'entity_id' => $this->product_package_id,
                'entity_type' => config('custom.entitytype.package_size'),
                'state_id' => $stateId,
            ])->first();
            $this->package_price = $packagePrice->price;
            $this->package_state_price_id = $packagePrice->id;
        }
        // Update item price and recalculate total
        $this->unit_price = $variantPrice->price;
        $this->state_price_id = $variantPrice->id;
        $this->options->fresh();
        $sumPriceOfOptions = $this->options->pluck('total')->sum();
        $total = $this->quantity * ($this->unit_price + $this->package_price) + $sumPriceOfOptions;
        $this->total = $total;
        $this->save();

        return true;
    }

    /**
     * Get cart item by encrypted ID and product ID
     *
     * Retrieves a specific cart item with options and add-ons for editing.
     * Validates item belongs to current cart and product.
     *
     * @param  string  $id  Encrypted cart item ID
     * @param  int  $productId  Product ID for validation
     * @return CartItem|null Cart item or null if not found
     */
    public static function getCartItemById($id, $productId)
    {
        // Get current cart and validate item access
        $cart = app(CartManager::class)->getActiveCart();
        $cartItem = '';
        if ($cart) {
            $item = CartItem::findByEncryptedId($id);
            if ($item) {
                // Load item with options and add-ons, validate cart and product
                $cartItem = $item
                    ->withoutAddons()
                    ->with([
                        'options',
                        'addons' => function ($query) {
                            return $query->with('options');
                        },
                    ])
                    ->where(['id' => $item->id, 'cart_id' => $cart->id, 'product_id' => $productId])
                    ->first();
            }
        }

        return $cartItem;
    }

    public function deleteAddons($id)
    {
        $addons = $this->where('addon_cartitem_id', $id)->get();
        if ($addons->isNotEmpty()) {
            foreach ($addons as $addon) {
                if ($addon->options->isNotEmpty()) {
                    $addon->options->each(function (CartOption $cartOption) use ($addon) {
                        $cartOption->where('cart_item_id', $addon->id)->delete();
                    });
                }
                if ($this->cart->coupon) {
                    CartItem::deleteFreeCartItem($addon, $this->cart->coupon);
                }
            }
            $this->where('addon_cartitem_id', $id)->delete();
        }
    }

    /**
     * Get box lunch cart items
     *
     * Retrieves all box lunch items from the current cart with options and add-ons.
     * Used for box lunch product display and management.
     *
     * @return \Illuminate\Support\Collection|null Formatted box lunch items
     */
    public static function getBoxLunchItems()
    {
        $cart = app(CartManager::class)->getActiveCart();
        if ($cart) {
            // Get box lunch items with related data
            $cartItems = CartItem::withoutAddons()
                ->with([
                    'options',
                    'addons' => function ($query) {
                        return $query->with(['options']);
                    },
                    'product',
                ])
                ->where(['cart_id' => $cart->id, 'box_lunch_type' => 1])
                ->get();

            return self::formatCartItems($cartItems);
        }
    }

    public static function formatCartItems($cartItems)
    {
        return $cartItems->map(function ($cartItem) {
            $cartItem->edit_url = $cartItem->edit_url;
            $addon_price = 0;
            if ($cartItem->addons) {
                foreach ($cartItem->addons as $addon) {
                    $addon->options = $addon->options;
                    $addon_price += $addon->total;
                }
                $cartItem->total = round($cartItem->total + $addon_price, 2);
            }

            return $cartItem;
        });
    }

    public static function getBoxLunchTotal($cartId)
    {
        $items = CartItem::with('addons')
            ->where(['cart_id' => $cartId, 'box_lunch_type' => 1])
            ->get();
        $cartItems = self::formatCartItems($items);

        return round($cartItems->sum('total'), 2);
    }

    public static function updateDiscount($id, $data)
    {
        $cartItem = CartItem::find($id);
        $cartItem->update($data);
    }

    public function deleteItemDiscount()
    {
        $this->discount = null;
        $this->free_delivery = 0;
        $this->save();
    }

    public static function getFreeCartItem($cartItemId)
    {
        return CartItem::where(['free_item_id' => $cartItemId, 'is_free_item' => 1])->first();
    }

    public static function deleteFreeCartItem($item, $coupon)
    {
        $query = CartItem::where(['cart_id' => $item->cart_id, 'is_free_item' => 1]);
        if (!$coupon->isOneFreeProduct()) {
            $query->where('free_item_id', $item->id);
        }
        $cartItem = $query->first();
        if ($cartItem) {
            CartOption::deleteByCartItemId($cartItem->id);
            $cartItem->delete();
        }
    }

    public static function getFreeCartItemsByCartId($cartId)
    {
        return CartItem::where(['cart_id' => $cartId, 'is_free_item' => 1])->get();
    }

    public static function getCartItemsCartId($cartId)
    {
        return CartItem::where(['cart_id' => $cartId, 'is_free_item' => 0])->get();
    }

    public static function getFreeCartItemByCartId($cartId)
    {
        return CartItem::with(['options'])
            ->where(['cart_id' => $cartId, 'is_free_item' => 1])
            ->first();
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function addons()
    {
        return $this->hasMany(self::class, 'addon_cartitem_id');
    }

    public function options()
    {
        return $this->hasMany(CartOption::class, 'cart_item_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'product_package_id');
    }

    public function dietary()
    {
        return $this->belongsTo(Dietary::class, 'product_dietary_id');
    }

    public function email()
    {
        return $this->invitee_id ? $this->invitee->email : 'My Order';
    }

    public function statePrice()
    {
        return $this->belongsTo(StatePrice::class, 'state_price_id')->where(['entity_type' => 'OjProductVariants']);
    }

    public function package_state_price()
    {
        return $this->belongsTo(StatePrice::class, 'package_state_price_id')->where([
            'entity_type' => 'OjPackageSizes',
        ]);
    }

    public function invitee()
    {
        return $this->belongsTo(Invitee::class, 'invitee_id');
    }

    public function variantWithoutSoftDelete()
    {
        return $this->variant()->withTrashed();
    }

    public function productWithoutSoftDelete()
    {
        return $this->product()->withTrashed();
    }

    public function dietaryWithoutSoftDelete()
    {
        return $this->dietary()->withTrashed();
    }

    /**
     * Get cart invitee relationship
     *
     * Gets the cart invitee record for group order tracking.
     *
     * @return CartInvitee|null Cart invitee record
     */
    public function cartInvitee()
    {
        return $this->hasOne(CartInvitee::class, 'invitee_id', 'invitee_id')->where('cart_id', $this->cart_id)->first();
    }
}
