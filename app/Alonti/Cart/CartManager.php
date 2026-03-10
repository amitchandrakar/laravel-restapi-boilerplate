<?php

declare(strict_types=1);

namespace App\Alonti\Cart;

use App\Alonti\Invitation\InvitationManager;
use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartOption;
use App\Models\Category;
use App\Models\FoodAvailableStore;
use App\Models\PackageSize;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductSelection;
use App\Models\ProductVariant;
use App\Models\StatePrice;
use Illuminate\Support\Facades\Auth;

/**
 * Cart Manager Service
 *
 * Core service class for cart operations in the Alonti ordering system:
 * - Cart creation and retrieval with session/user context
 * - Group order invitee cart management
 * - Cart item validation and availability checking
 * - Product, variant, and option availability validation
 * - State-based pricing updates
 * - Cart count calculations
 * - Add-on and option management
 * - Warm cookie banner logic
 * - Tip calculation utilities
 */
class CartManager
{
    /**
     * Get total count of items in active cart
     *
     * Returns the total number of cart items for the current user's active cart.
     * Handles both regular users and group order invitees.
     *
     * @return int Total count of cart items
     */
    public function getCartCount()
    {
        $count = 0;
        $cart = $this->getActiveCart();
        if ($cart) {
            $count = $cart->getCartCount();
        }

        return $count;
    }

    /**
     * Get the currently active cart
     *
     * Retrieves the active cart based on user context:
     * - For group order invitees: returns invitee's cart via InvitationManager
     * - For regular users: finds cart by session ID or user ID
     * - Loads cart with order relationship for status checking
     *
     * @return Cart|null Active cart or null if none exists
     */
    public function getActiveCart()
    {
        $requestFromInvitee = config('app.request-from-invitee');

        if ($requestFromInvitee) {
            $invitationManager = app(InvitationManager::class);

            return $invitationManager->getCart();
        }

        $userId = Auth::user() ? Auth::user()->id : '';
        $sessionId = session()->getId();
        $cartId = $this->checkCartExists($sessionId, $userId);

        return Cart::with('order')->find($cartId);
    }

    /**
     * Check if a cart exists for session or user
     *
     * Searches for existing cart using multiple approaches:
     * 1. First checks user's active cart ID if user is logged in
     * 2. Falls back to session-based cart lookup
     * 3. Matches both session ID and user ID for authenticated users
     *
     * @param  string  $sessionId  Current session identifier
     * @param  int|string  $userId  User ID if authenticated
     * @return int|null Cart ID if found, null otherwise
     */
    public function checkCartExists($sessionId, $userId)
    {
        $cartId = $this->getUserActiveCartId($userId);

        if (!$cartId) {
            $cart = Cart::where('session_id', $sessionId);

            if (!empty($userId)) {
                $cart->where('user_id', $userId);
            }

            $cart = $cart->first();

            if ($cart) {
                $cartId = $cart->id;
            } else {
                $cartId = null; // TODO: Review this code later
            }
        }

        return $cartId;
    }

    /**
     * Get authenticated user's active cart ID
     *
     * Returns the cart ID that the current authenticated user has marked as active.
     * Used for cart persistence across sessions for logged-in users.
     *
     * @return int|null Active cart ID or null if no user or no active cart
     */
    public function getUserActiveCartId()
    {
        $user = Auth::user();

        return $user ? $user->active_cart_id : null;
    }

    /**
     * Create a new cart with delivery area information
     *
     * Creates a new cart and associates it with:
     * - Current session or authenticated user
     * - Delivery area (state, zipcode, cafe)
     * - Abandoned cart tracking record
     *
     * Validates that delivery area has an associated cafe before creation.
     *
     * @param  object  $zipManager  ZipManager instance with delivery area info
     * @return Cart|null New cart instance or null if no cafe available
     */
    public static function createCart($zipManager)
    {
        $cart = new Cart();
        $cart->session_id = session()->getId();
        if (Auth::user()) {
            $cart->session_id = null;
            $cart->user_id = Auth::user()->id;
        }
        $deliveryArea = $zipManager->alontiDeliveryArea;
        if (!$deliveryArea->cafe) {
            return;
        } else {
            if ($deliveryArea) {
                $cart->state_id = $deliveryArea->state_id;
                $cart->zipcode = $deliveryArea->zipcode;
                $cart->cafe_id = $deliveryArea->cafe->id;
            }
            $cart->save();
            if ($cart) {
                // Create abandoned cart tracking record
                $data = [
                    'session_id' => $cart->session_id,
                    'cart_id' => $cart->id,
                    'alonti_user_id' => Auth::user() ? Auth::user()->id : null,
                    'cafe_id' => $deliveryArea->cafe->cafe_id,
                ];
                AbandonedCart::create($data);
            }

            return $cart;
        }
    }

    /**
     * Save cart item with complete product information
     *
     * Creates or updates a cart item with:
     * - Product and variant pricing based on delivery state
     * - Package size pricing if applicable
     * - Box lunch type detection for bulk categories
     * - Invitee assignment for group orders
     * - Tags and special instructions
     * - Discount and free item handling
     * - Total price calculation
     *
     * @param  array  $input  Cart item data from request
     * @param  Cart  $cart  Target cart instance
     * @param  CartItem  $cartItem  Cart item to populate
     * @param  int|null  $addon_cartitem_id  Parent item ID if this is an add-on
     * @return CartItem|null Saved cart item or null if product/variant not found
     */
    public static function saveCartItem($input, $cart, $cartItem, $addon_cartitem_id = null)
    {
        $stateId = $cart->state_id;
        $product = Product::find($input['product_id']);
        $variant = ProductVariant::find($input['product_variant_id']);
        if ($product && $variant) {
            // Auto-detect box lunch type for bulk categories (type 2)
            if ($product->category && $product->category->type == 2) {
                $input['box_lunch_type'] = 1;
            }
            $productPrice = StatePrice::getPrice($variant->id, ProductVariant::ENTITY_TYPE, $stateId);
            $cartItem->cart_id = $cart->id;
            $cartItem->product_id = $product->id;
            $cartItem->category_id = $product->category_id;
            $cartItem->product_variant_id = $variant->id;
            $cartItem->quantity = $input['quantity'];
            $cartItem->unit_price = isset($input['price']) ? $input['price'] : $productPrice->price;
            $cartItem->product_description = $product->description;

            // Process tags for "who is this for" field
            $tags = [];
            if (isset($input['tags']) && !empty($input['tags'])) {
                foreach ($input['tags'] as $tag) {
                    $tags[] = $tag['text'];
                }
            }
            $cartItem->who_is_this_for = implode(',', $tags);
            $cartItem->special_instruction = isset($input['special_instruction']) ? $input['special_instruction'] : '';
            $cartItem->box_lunch_type = isset($input['box_lunch_type']) ? $input['box_lunch_type'] : 0;
            $cartItem->product_name = $product->name;
            $cartItem->state_price_id = $productPrice->id;
            $cartItem->addon_cartitem_id = $addon_cartitem_id;
            $cartItem->discount = isset($input['discount']) ? $input['discount'] : null;
            $cartItem->free_item_id = isset($input['free_item_id']) ? $input['free_item_id'] : null;
            $cartItem->is_free_item = isset($input['is_free_item']) ? $input['is_free_item'] : 0;
            $cartItem->is_invitee_default_meal = isset($input['invitee_default_meal']) ? 1 : 0;

            // Handle package size pricing if specified
            if (isset($input['product_package_id']) && $input['product_package_id']) {
                $packageSize = PackageSize::find($input['product_package_id']);
                if ($packageSize) {
                    $packageSizePrice = StatePrice::getPrice($packageSize->id, PackageSize::ENTITY_TYPE, $stateId);
                    $cartItem->product_package_id = $packageSize->id;
                    $cartItem->package_size = $packageSize->size;
                    $cartItem->package_price = $packageSizePrice->price;
                    $cartItem->package_state_price_id = $packageSizePrice->id;
                }
            }

            // Assign invitee ID for group order items
            if (config('app.request-from-invitee') || isset($input['invitee_default_meal'])) {
                $cartItem->invitee_id = isset($input['invitee_default_meal'])
                    ? $input['invitee_id']
                    : session()->get('invitation.invitee_id');
            }

            // Calculate item total (base + package price) * quantity
            $cartItem->total = $cartItem->quantity * ($cartItem->unit_price + $cartItem->package_price);
            $cartItem->save();

            return $cartItem;
        }
    }

    /**
     * Create cart item options with state-based pricing
     *
     * Adds product options/selections to a cart item with:
     * - State-specific pricing for each selection
     * - Quantity calculations based on minimum serve requirements
     * - Division by zero prevention for quantity calculations
     * - Total price recalculation including option costs
     *
     * @param  CartItem  $cartItem  Cart item to add options to
     * @param  int  $stateId  State ID for pricing calculations
     * @param  array  $cartItemOptions  Array of option selections
     * @return void
     */
    public static function createCartItemOptions($cartItem, $stateId, $cartItemOptions)
    {
        $sel_price = 0;
        if (!empty($cartItemOptions)) {
            $product = Product::find($cartItem->product_id);
            foreach ($cartItemOptions as $option) {
                $selection = ProductSelection::find($option['product_selection_id']);
                if ($selection) {
                    $selectionPrice = StatePrice::getPrice($selection->id, ProductSelection::ENTITY_TYPE, $stateId);
                    $cartOption = new CartOption();
                    $cartOption->cart_item_id = $cartItem->id;
                    $cartOption->product_option_id = $option['product_option_id'];
                    $cartOption->product_selection_id = $selection->id;
                    $cartOption->name = $selection->name;
                    $cartOption->unit_price = $selectionPrice->price;

                    // Calculate option quantity based on minimum serve requirements
                    // Prevent division by zero error
                    $qty =
                        $product->minimum_serve == 10 && $product->minimum_serve > 0
                            ? $cartItem->quantity / $product->minimum_serve
                            : $cartItem->quantity;
                    $cartOption->quantity = $qty;
                    $cartOption->total = $selectionPrice->price * $qty;
                    $cartOption->state_price_id = $selectionPrice->id;
                    $cartOption->save();
                    $sel_price += $cartOption->total;
                }
            }
        }

        // Recalculate cart item total including option prices
        $cartItem->total = $cartItem->quantity * ($cartItem->unit_price + $cartItem->package_price) + $sel_price;
        $cartItem->save();
    }

    /**
     * Create add-on items for a cart item
     *
     * Processes add-on products that are associated with a main cart item:
     * - Creates separate CartItem records for each add-on
     * - Links add-ons to parent item via addon_cartitem_id
     * - Processes options for each add-on item if present
     *
     * @param  Cart  $cart  Target cart instance
     * @param  CartItem  $cartItem  Parent cart item
     * @param  array  $addons  Array of add-on product data
     * @return void
     */
    public static function createCartAddons($cart, $cartItem, $addons)
    {
        if (!empty($addons)) {
            foreach ($addons as $addon) {
                $addonCartItem = new CartItem();
                $addonCartItem = self::saveCartItem($addon, $cart, $addonCartItem, $cartItem->id);

                // Process options for the add-on item if specified
                if (isset($addon['cartOptions'])) {
                    self::createCartItemOptions($addonCartItem, $cart->state_id, $addon['cartOptions']);
                }
            }
        }
    }

    /**
     * Check if cart contains only warm cookies and determine banner display
     *
     * Analyzes cart contents to determine UI display logic for:
     * - Warm cookie banner: Shows when cart has regular items (not just cookies)
     * - Gift display: Shows when cart has only delivery exception items
     *
     * Logic:
     * - Delivery exception categories have special delivery rules
     * - Cookie items within exception categories don't trigger banner
     * - Regular items trigger warm cookie banner display
     *
     * @param  Cart  $cart  Cart to analyze
     * @return array ['displayWcBanner' => bool, 'giftToDisplay' => bool]
     */
    public static function checkCartHasOnlyWarmCookie($cart)
    {
        $deliveryExceptionCategories = Category::where('delivery_exception', 1)->pluck('name');
        $cartItemCategories = [];

        if ($cart->items) {
            $items = CartItem::getCartItemsCartId($cart->id);
            $cartItemCategories = $items->map(function ($item, $key) {
                return $item->category->name;
            });
        }

        $cartItemCategories->unique();
        $displayWcBanner = false;

        // Check if warm cookie banner should be displayed
        $cartItemCategories->each(function ($item) use ($deliveryExceptionCategories, &$displayWcBanner) {
            $displayWcBanner = !$deliveryExceptionCategories->contains($item);
            if (!$deliveryExceptionCategories->contains($item)) {
                $displayWcBanner = true;
            } else {
                // Special handling for cookie items within exception categories
                if (stripos($item, 'cookie') !== false) {
                    $displayWcBanner = false;

                    return false;
                } else {
                    $displayWcBanner = true;
                }
            }
        });

        // Check if gift should be displayed (only exception category items)
        $giftToDisplay = true;
        $cartItemCategories->each(function ($item) use ($deliveryExceptionCategories, &$giftToDisplay) {
            $displayWcBanner = !$deliveryExceptionCategories->contains($item);
            if (!$deliveryExceptionCategories->contains($item)) {
                $giftToDisplay = false;

                return false;
            } else {
                $giftToDisplay = true;
            }
        });

        return ['displayWcBanner' => $displayWcBanner, 'giftToDisplay' => $giftToDisplay];
    }

    /**
     * Generate tip calculation options for cart
     *
     * Creates tip percentage options with calculated amounts based on cart total:
     * - Calculates tip on subtotal + delivery fee + sales tax
     * - Provides 10%, 15%, 20% options with dollar amounts
     * - Includes "decide later" option
     *
     * @param  Cart  $cart  Cart to calculate tip options for
     * @return array Tip options with percentages and calculated amounts
     */
    public static function getTipOption($cart)
    {
        $tipOptions = [];
        $tipOptions['0'] = 'Decide later';

        // Calculate tip base amount (excludes gratuity)
        $tipTotal = $cart->taxable + $cart->nontaxable + $cart->delivery_fee + $cart->sales_tax;

        // Generate percentage options with calculated dollar amounts
        $tipOptions['10'] = '10% ($' . round(($tipTotal * 10) / 100, 2) . ')';
        $tipOptions['15'] = '15% ($' . round(($tipTotal * 15) / 100, 2) . ')';
        $tipOptions['20'] = '20% ($' . round(($tipTotal * 20) / 100, 2) . ')';

        return $tipOptions;
    }

    /**
     * Validate cart items against store availability
     *
     * Comprehensive validation of all cart items to ensure they are available at the target cafe:
     * - Checks category, product, variant, and option availability
     * - Handles both regular cart items and invitee items for group orders
     * - Returns detailed validation results with error messages
     *
     * @param  Cart  $cart  Cart to validate
     * @param  array  $inviteeItems  Optional invitee items for group order validation
     * @return array ['status' => bool, 'msg' => string] Validation result
     */
    public function storeItemValidation($cart, $inviteeItems = [])
    {
        $requestFromInvitee = config()->get('app.request-from-invitee');
        $items = $requestFromInvitee ? $inviteeItems : $cart->items;
        $result['status'] = true;

        if (empty($cart) || empty($items) || count($items) == 0) {
            $result['status'] = false;
            $result['msg'] = 'Cart is empty, please add items to cart and proceed.';
        } else {
            $cafeId = $cart->cafe_id;
            $cafeName = $cart->cafe ? $cart->cafe->cafename : '';

            // Collect all IDs for batch availability checking
            $categoryIds = [];
            $productIds = [];
            $variantIds = [];
            $optionIds = [];
            $selectionIds = [];

            foreach ($items as $key => $value) {
                $categoryIds[] = $value->category_id;
                $productIds[] = $value->product_id;
                $variantIds[] = $value->product_variant_id;

                foreach ($value->options as $opt) {
                    $optionIds[] = $opt->product_option_id;
                    $selectionIds[] = $opt->product_selection_id;
                }
            }

            // Perform availability checks for each entity type
            $categoryData = [];
            $productData = [];
            $variantData = [];
            $optionData = [];

            if (!empty($categoryIds)) {
                $categoryData = $this->checkCategoryAvailability($categoryIds, $cafeId);
            }

            if (!empty($productIds)) {
                $productData = $this->checkProductAvailability($productIds, $cafeId);
            }

            if (!empty($variantIds)) {
                $variantData = $this->checkVariantAvailability($variantIds, $cafeId);
            }

            if (!empty($optionIds)) {
                $optionData = $this->checkOptionAvailability($optionIds, $cafeId);
            }

            // Merge all validation results
            $data = array_merge($categoryData, $productData, $variantData, $optionData);

            // Generate validation message
            $result = $this->itemValidationMsg($data, $result, $cafeName);
            $result['msg'] =
                $result['msg'] != ''
                    ? $result['msg'] .
                        $cafeName .
                        ', please delete the unavailable products and choose from the available products and proceed.'
                    : '';
        }

        return $result;
    }

    /**
     * Check availability of specific item and add-ons before adding to cart
     *
     * Validates a single item request against cafe availability:
     * - Main item validation (product, variant, options)
     * - Add-on item validation for each add-on
     * - Uses session delivery info if no cart provided
     * - Returns comprehensive status for UI feedback
     *
     * @param  Cart|null  $cart  Cart context (optional)
     * @param  array  $input  Item data to validate
     * @return array ['status' => bool, 'msg' => string] Validation result
     */
    public function itemAvailability($cart = null, $input = [])
    {
        // Determine cafe context from cart or session
        if ($cart) {
            $cafeId = $cart->cafe_id;
            $cafeName = $cart->cafe ? $cart->cafe->cafename : '';
        } else {
            $deliveryAreaInfo = session()->has('UserDeliveryInformation')
                ? session()->get('UserDeliveryInformation')
                : [];
            $cafeId = !empty($deliveryAreaInfo) ? $deliveryAreaInfo['alontiDeliveryArea']['cafe']['id'] : '';
            $cafeName = !empty($deliveryAreaInfo) ? $deliveryAreaInfo['alontiDeliveryArea']['cafe']['cafename'] : '';
        }

        $response['item']['status'] = true;
        $response['addon']['status'] = true;

        if (empty($cafeId)) {
            // No cafe context available for validation
        } else {
            // Validate main item
            $addons = isset($input['addons']) && !empty($input['addons']) ? $input['addons'] : [];
            $mainData = $this->checkAllRequestDataAvailability($input, $cafeId);
            $res = $this->itemValidationMsg($mainData, $result);
            if (!$res['status']) {
                $response['item']['status'] = false;
                $response['item']['msg'] = $res['msg'];
            }

            // Validate each add-on item
            foreach ($addons as $key => $value) {
                $addonData = $this->checkAllRequestDataAvailability($value, $cafeId);
                $res = $this->itemValidationMsg($addonData, $result);
                if (!$res['status']) {
                    $response['addon']['status'] = false;
                    $response['addon']['msg'][] = $res['msg'];
                }
            }
        }

        // Compile final validation result
        $returnVal['status'] = !$response['item']['status'] || !$response['addon']['status'] ? false : true;
        $returnVal['msg'] = '';

        if (!$response['item']['status']) {
            $returnVal['msg'] .= $response['item']['msg'];
        }
        if (!$response['addon']['status']) {
            if ($returnVal['msg'] != '') {
                $returnVal['msg'] .= ' and ';
            }
            $returnVal['msg'] .= 'Addons ' . implode(',', $response['addon']['msg']);
        }
        $returnVal['msg'] = $returnVal['msg'] != '' ? $returnVal['msg'] . $cafeName : $returnVal['msg'];

        return $returnVal;
    }

    /**
     * Check availability of all components in item request
     *
     * Validates all aspects of a single item request:
     * - Product and its category availability
     * - Product variant availability
     * - Option selections availability (if present)
     *
     * @param  array  $input  Item request data
     * @param  int  $cafeId  Cafe ID to check availability against
     * @return array Merged availability data for all components
     */
    public function checkAllRequestDataAvailability($input, $cafeId)
    {
        // Check product availability (includes category data)
        $mainProductData = $this->checkProductAvailability($input['product_id'], $cafeId);
        $mainCategoryData = $this->checkCategoryAvailability($mainProductData['product']['category'], $cafeId);
        unset($mainProductData['product']['category']);

        // Check variant availability
        $mainVariantData = $this->checkVariantAvailability($input['product_variant_id'], $cafeId);

        // Check option availability if options are specified
        if (isset($input['cartOptions']) && !empty($input['cartOptions'])) {
            $optionIds = array_map(function ($val) {
                return $val['product_option_id'];
            }, $input['cartOptions']);
            $mainOptionData = $this->checkOptionAvailability($optionIds, $cafeId);
            $data = array_merge($mainCategoryData, $mainProductData, $mainVariantData, $mainOptionData);
        } else {
            $data = array_merge($mainCategoryData, $mainProductData, $mainVariantData);
        }

        return $data;
    }

    /**
     * Check category availability at specific cafe
     *
     * Validates if categories are available at the target cafe:
     * - Skips validation for categories available at all stores
     * - Checks FoodAvailableStore records for store-specific availability
     * - Returns status and list of unavailable category names
     *
     * @param  array|int  $categoryIds  Category ID(s) to check
     * @param  int  $cafeId  Cafe ID to check availability against
     * @return array ['category' => ['status' => bool, 'name' => array]]
     */
    public function checkCategoryAvailability($categoryIds, $cafeId)
    {
        $data['category']['status'] = false;
        $data['category']['name'] = [];

        $categoryData = app(Category::class)->getCategoryById($categoryIds);
        foreach ($categoryData as $key => $value) {
            // Only check availability for categories not available at all stores
            if (!$value->available_all_store) {
                $availableData = app(FoodAvailableStore::class)->getDataBasedEntityCafe(
                    $cafeId,
                    $value->id,
                    'OjCategories'
                );
                if (empty($availableData)) {
                    $data['category']['name'][] = $value->name;
                }
            }
        }

        // Mark as available if no unavailable categories found
        if (empty($data['category']['name'])) {
            $data['category']['status'] = true;
        }

        return $data;
    }

    /**
     * Check product availability at specific cafe
     *
     * Validates if products are available at the target cafe:
     * - Collects category IDs for additional validation
     * - Skips validation for products available at all stores
     * - Checks FoodAvailableStore records for store-specific availability
     * - Returns status and list of unavailable product names
     *
     * @param  array|int  $productIds  Product ID(s) to check
     * @param  int  $cafeId  Cafe ID to check availability against
     * @return array ['product' => ['status' => bool, 'name' => array, 'category' => array]]
     */
    public function checkProductAvailability($productIds, $cafeId)
    {
        $data['product']['status'] = false;
        $data['product']['name'] = [];
        $data['product']['category'] = [];

        $productData = app(Product::class)->fetchProductById($productIds);
        foreach ($productData as $key => $value) {
            // Collect category IDs for category availability checking
            $data['product']['category'][] = $value->category_id;

            // Only check availability for products not available at all stores
            if (!$value->available_all_store) {
                $availableData = app(FoodAvailableStore::class)->getDataBasedEntityCafe(
                    $cafeId,
                    $value->id,
                    'OjProducts'
                );
                if (empty($availableData)) {
                    $data['product']['name'][] = $value->name;
                }
            }
        }

        // Mark as available if no unavailable products found
        if (empty($data['product']['name'])) {
            $data['product']['status'] = true;
        }

        return $data;
    }

    /**
     * Check product variant availability at specific cafe
     *
     * Validates if product variants are available at the target cafe:
     * - Skips validation for variants available at all stores
     * - Checks FoodAvailableStore records for store-specific availability
     * - Returns status and list of unavailable variant names
     *
     * @param  array|int  $variantIds  Variant ID(s) to check
     * @param  int  $cafeId  Cafe ID to check availability against
     * @return array ['variant' => ['status' => bool, 'name' => array]]
     */
    public function checkVariantAvailability($variantIds, $cafeId)
    {
        $data['variant']['status'] = false;
        $data['variant']['name'] = [];

        $variantData = app(ProductVariant::class)->getVariantById($variantIds);
        foreach ($variantData as $key => $value) {
            // Only check availability for variants not available at all stores
            if (!$value->available_all_store) {
                $availableData = app(FoodAvailableStore::class)->getDataBasedEntityCafe(
                    $cafeId,
                    $value->id,
                    'OjProductVariants'
                );
                if (empty($availableData)) {
                    $data['variant']['name'][] = $value->name;
                }
            }
        }

        // Mark as available if no unavailable variants found
        if (empty($data['variant']['name'])) {
            $data['variant']['status'] = true;
        }

        return $data;
    }

    /**
     * Check product option availability at specific cafe
     *
     * Validates if product options are available at the target cafe:
     * - Skips validation for options available at all stores
     * - Checks FoodAvailableStore records for store-specific availability
     * - Collects selection names for unavailable options
     * - Returns status and list of unavailable selection names
     *
     * @param  array|int  $optionIds  Option ID(s) to check
     * @param  int  $cafeId  Cafe ID to check availability against
     * @return array ['option' => ['status' => bool, 'name' => array]]
     */
    public function checkOptionAvailability($optionIds, $cafeId)
    {
        $data['option']['status'] = false;
        $data['option']['name'] = [];

        $optionData = app(ProductOption::class)->getOptionById($optionIds);
        foreach ($optionData as $key => $value) {
            // Only check availability for options not available at all stores
            if (!$value->available_all_store) {
                $availableData = app(FoodAvailableStore::class)->getDataBasedEntityCafe(
                    $cafeId,
                    $value->id,
                    'OjProductOptions'
                );
                if (empty($availableData)) {
                    // Collect selection names for unavailable options
                    $selectionData = $value->selections;
                    foreach ($selectionData as $selection) {
                        $data['option']['name'][] = $selection->name;
                    }
                }
            }
        }

        // Mark as available if no unavailable options found
        if (empty($data['option']['name'])) {
            $data['option']['status'] = true;
        }

        return $data;
    }

    /**
     * Generate validation message from availability check results
     *
     * Creates user-friendly error messages for unavailable items:
     * - Combines category, product, variant, and option failures
     * - Uses proper grammar for single vs multiple items
     * - Returns structured result with status and message
     *
     * @param  array  $data  Availability check results
     * @param  array  $result  Reference to result array to modify
     * @return array Updated result with status and message
     */
    public function itemValidationMsg($data, &$result)
    {
        $msg = '';
        $count = 0;

        // Build message for each type of unavailable item
        if (isset($data['category']) && !$data['category']['status']) {
            $msg .= 'Category (' . implode(', ', $data['category']['name']) . ')';
            $count += count($data['category']['name']);
        }

        if (isset($data['product']) && !$data['product']['status']) {
            if ($msg != '') {
                $msg .= ' and ';
            }
            $msg .= 'Product (' . implode(', ', $data['product']['name']) . ')';
            $count += count($data['product']['name']);
        }

        if (isset($data['variant']) && !$data['variant']['status']) {
            if ($msg != '') {
                $msg .= ' and ';
            }
            $msg .= 'Variant (' . implode(', ', $data['variant']['name']) . ')';
            $count += count($data['variant']['name']);
        }

        if (isset($data['option']) && !$data['option']['status']) {
            if ($msg != '') {
                $msg .= ' and ';
            }
            $msg .= 'Sides (' . implode(', ', $data['option']['name']) . ')';
            $count += isset($data['optionCount']) ? count($data['optionCount']['name']) : 0;
        }

        // Add proper grammar for the error message
        if ($count > 0) {
            $msg .= $count > 1 ? ' are ' : ' is ';
            $msg .= 'not available for cafe ';
        }

        $result['msg'] = $msg;
        $result['status'] = $count > 0 ? false : true;

        return $result;
    }
}
