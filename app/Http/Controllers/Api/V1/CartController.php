<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Alonti\Cart\CartManager;
use App\Alonti\Coupon\UpdateCoupon;
use App\Alonti\Invitation\InvitationManager;
use App\Alonti\User\UserManager;
use App\Alonti\ZipManager\ZipManager;
use App\Http\Requests\Api\V1\CartAddRequest;
use App\Http\Requests\Api\V1\CartUpdateRequest;
use App\Http\Resources\Api\V1\CartDeliveryResource;
use App\Http\Resources\Api\V1\CartPaymentResource;
use App\Http\Resources\Api\V1\CartReviewResource;
use App\Http\Resources\Api\V1\CartServingOptionsResource;
use App\Http\Resources\Api\V1\CartSummaryResource;
use App\Models\Cart;
use App\Models\CartInvitee;
use App\Models\CartItem;
use App\Models\CartOption;
use App\Models\Category;
use App\Models\Cim;
use App\Models\CimPaymentProfile;
use App\Models\Configuration;
use App\Models\DisableDate;
use App\Models\GroupOrder;
use App\Models\Industry;
use App\Models\Offmenu;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Reward;
use App\Models\ServingOption;
use App\Models\Setting;
use App\Models\State;
use App\Models\Time;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Cart Controller
 *
 * Handles all cart-related operations including:
 * - Cart display and management
 * - Adding/updating/removing cart items
 * - Group order functionality
 * - Cart checkout and payment processing
 * - Coupon application and validation
 */
class CartController extends Controller
{
    /**
     * Display the shopping cart page
     *
     * Shows the cart contents with all items, pricing, and group order information.
     * Handles both individual and group order carts with different display logic.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index()
    {
        // Get the active cart for current user/session
        $cartInfo = app(CartManager::class)->getActiveCart();

        // Return error if no cart exists
        if (!$cartInfo) {
            return $this->errorResponse('Your bag is empty, please add items to your bag', 400);
        }

        // Return error if individual cart is empty
        if (!$cartInfo->isGroupOrder() && $cartInfo->items && $cartInfo->items->count() == 0) {
            return $this->errorResponse('Your bag is empty, please add items to your bag', 400);
        }

        // Flag to control reminder email functionality for group orders
        $allowLeaderToSendReminderEmail = true;

        // Handle group order timing logic for reminder emails
        if ($cartInfo->group_order_id && $cartInfo->groupOrderConfig) {
            // Calculate response deadline with timezone adjustment
            $responseTime = strtotime(
                $cartInfo->groupOrderConfig->response_date . ' ' . $cartInfo->groupOrderConfig->response_time
            );
            $timeZone = abs($cartInfo->cafe->market->timezone_difference);
            $timeZoneHours = strtotime('-' . $timeZone . ' hours');

            // Disable reminders if deadline has passed
            if ($timeZoneHours >= $responseTime) {
                $allowLeaderToSendReminderEmail = false;
            }
        }

        // Handle default meal assignment for pending invitees
        $requestFromInvitee = config()->get('app.request-from-invitee');
        if (!$requestFromInvitee && $cartInfo->groupOrderConfig && $cartInfo->groupOrderConfig->default_meal) {
            // Get invitees who haven't completed their orders
            $pendingInviteeIds = $cartInfo->inviteeNotCompleted([1, 2]);

            // Assign default meals if deadline passed and reminders are disabled
            if (!empty($pendingInviteeIds) && !$allowLeaderToSendReminderEmail) {
                self::saveInviteeDefaultMeal($cartInfo->id, $pendingInviteeIds);
            }
        }

        // Handle warm cookie banner logic
        $bannerValidation = CartManager::checkCartHasOnlyWarmCookie($cartInfo);
        $displayWcBanner = $bannerValidation['displayWcBanner'];

        // Clear personalized message if only warm cookies in cart
        if ($displayWcBanner && $cartInfo->personalized_message != '') {
            $cartInfo->personalized_message = null;
            $cartInfo->save();
        }

        // Get warm cookie category data for potential upselling
        $warmCookieData = Category::where('name', 'Special Occasion Cookies')
            ->orWhere('name', 'Warm Cookies')
            ->with('uniqueurl')
            ->first();

        // Prepare cart items query
        $items = $cartInfo->items();

        // Calculate invitee-specific total if applicable
        if (config('app.request-from-invitee')) {
            $invitee_total = round($cartInfo->totalForInvitee(session()->get('invitation.invitee_id')), 2);
        } else {
            $invitee_total = 0; // Initialize if not set
        }

        // Get cart items excluding add-ons but including them as relationships
        $items = $items->withoutAddons()->with('addons');

        $loggedInUser = auth()->user();
        $inviteeId = session('invitation.invitee_id');
        // $firstItem = $items->first();
        $firstItem = $items instanceof \Illuminate\Database\Eloquent\Builder ? (clone $items)->first() : null;

        // Determine if the cart belongs to the logged-in user
        $cartBelongsToLoggedInUser =
            $loggedInUser && $firstItem && $firstItem->cart && $firstItem->cart->user_id === $loggedInUser->id;

        if ($cartBelongsToLoggedInUser) {
        } elseif ($inviteeId) {
            $items = $items->where('invitee_id', $inviteeId);
        }

        $items = $items->get();

        // Initialize group order details
        $groupDetail = [];
        $ownerCount = 0;

        if ($cartInfo->isGroupOrder()) {
            $groupDetail = GroupOrder::getGroupOrderDetails($cartInfo)->where('id', $cartInfo->group_order_id)->first();
            $ownerCount = round($groupDetail->cart->ownerCount);
        }

        session()->remove('via-guest-checkout');
        $itemCountInvitee = 0;

        if (config('app.request-from-invitee')) {
            $itemCountInvitee = app(InvitationManager::class)->getInviteeCartCount();
        }

        $itemCount = app(CartManager::class)->getCartCount();
        $deliveryAreaCount = session()->has('UserDeliveryInformation.alontiDeliveryAreaCount')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaCount')
            : 0;
        $deliveryAreaChosen = session()->has('UserDeliveryInformation.deliveryAreaChosen')
            ? session()->get('UserDeliveryInformation.deliveryAreaChosen')
            : false;
        $cafeList = session()->has('UserDeliveryInformation.alontiDeliveryAreaList')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaList')
            : [];
        $budget = 0;
        $goConfigExist = false;
        $budgetActive = false;

        if ($requestFromInvitee) {
            if ($cartInfo->groupOrderConfig) {
                $budget = $cartInfo->groupOrderConfig->invitee_budget;
                $budgetActive = $cartInfo->groupOrderConfig->invitee_budget > 0 ? true : false;
                $goConfigExist = $cartInfo->groupOrderConfig ? true : false;
            }
        }

        $productName = '';
        if ($cartInfo->groupOrderConfig && $cartInfo->groupOrderConfig->default_meal) {
            $productName = '';
            $product = Product::where('id', $cartInfo->groupOrderConfig->product_id)
                ->with(['variant'])
                ->first();
            $productName .= $product->name;
        }

        $pendingInvitees = 0;
        if ($cartInfo->isGroupOrder()) {
            $pendingInvitees = $cartInfo->responseCount([1, 2]);
        }

        // Get serving ware options from offmenu table having cart_id = $cartInfo->id
        $servingOption = Offmenu::where('cart_id', $cartInfo->id)->first();

        $group = GroupOrder::find(session()->get('invitation.group_order_id'));

        return $this->successResponse(
            CartSummaryResource::make([
                'group' => $group,
                'cart_info' => $cartInfo,
                'group_detail' => $groupDetail,
                'items' => $items,
                'display_wc_banner' => $displayWcBanner,
                'warm_cookie_data' => $warmCookieData,
                'item_count' => $itemCount,
                'item_count_invitee' => $itemCountInvitee,
                'owner_count' => $ownerCount,
                'delivery_area_count' => $deliveryAreaCount,
                'delivery_area_chosen' => $deliveryAreaChosen,
                'cafe_list' => $cafeList,
                'budget' => $budget,
                'request_from_invitee' => $requestFromInvitee,
                'go_config_exist' => $goConfigExist,
                'allow_leader_to_send_reminder_email' => $allowLeaderToSendReminderEmail,
                'budget_active' => $budgetActive,
                'product_name' => $productName,
                'pending_invitees' => $pendingInvitees,
                'invitee_total' => $invitee_total,
            ]),
            'Success'
        );
    }

    /**
     * Display the delivery details page
     *
     * Shows delivery address form, past delivery addresses, and delivery options.
     * Handles both delivery and pickup scenarios with validation and timezone logic.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function delivery()
    {
        // Get active cart and validate it has items
        $cartInfo = app(CartManager::class)->getActiveCart();

        if (!$cartInfo) {
            return $this->errorResponse('Cart not found.', 400);
        }

        if ($cartInfo->items->count() == 0) {
            return $this->errorResponse('Your cart is empty', 400);
        }

        $displayWcPersonalisedMsg = CartManager::checkCartHasOnlyWarmCookie($cartInfo);
        $pastDeliveryAddress = UserManager::getRecentDeliveryAddress();
        $existingDeliveryAddressCount = $pastDeliveryAddress->count();

        $industries = Industry::pluck('name', 'id');
        $industries->prepend(' -- Select Industry -- ', '');

        $states = State::where('status', 1)->pluck('name', 'id');
        $states->prepend(' -- Select state -- ', '');

        $deliveryAreaCafe = [];
        $pickupZipcode = false;
        $pickupCafes = session()->get('UserDeliveryInformation.pickup.cafes');

        if (session()->get('UserDeliveryInformation.pickup.givenZipCode')) {
            $pickupZipcode = true;
        } else {
            $givenZipCode = ''; // Initialize if not set
        }

        if ($cartInfo->shipping && $cartInfo->shipping->delivery_date) {
            $cartInfo->shipping->delivery_date = Carbon::createFromFormat(
                'Y-m-d',
                $cartInfo->shipping->delivery_date
            )->format('m/d/Y');
        }

        if ($cartInfo->shipping && $cartInfo->shipping->address1 != '') {
            $cartInfo->shipping->address1 = str_ireplace(
                $cartInfo->shipping->zipcode,
                '',
                $cartInfo->shipping->address1
            );
            $cartInfo->shipping->address1 = str_ireplace($cartInfo->shipping->city, '', $cartInfo->shipping->address1);
            $cartInfo->shipping->address1 = str_ireplace(', ,', '', $cartInfo->shipping->address1);
        }

        $displayWcPersonalisedMsg = $displayWcPersonalisedMsg['displayWcBanner'];

        if (Auth::user() && $cartInfo->abandonedCart && !$cartInfo->abandonedCart->alonti_user_id) {
            $cartInfo->abandonedCart->alonti_user_id = Auth::user()->id;
            $cartInfo->abandonedCart->update();
        }

        $disable_dates = DisableDate::all();

        $deliveryTimes = config('custom.delivery_pickup_time');

        if ($cartInfo->cafe && $cartInfo->cafe->market && $cartInfo->cafe->market->allow_night_orders == 1) {
            $deliveryTimes = config('custom.day_night_delivery_pickup_time');
        }

        $allowWeekendOrders =
            $cartInfo->cafe && $cartInfo->cafe->market && $cartInfo->cafe->market->allow_weekend_orders == 1
                ? true
                : false;

        return $this->successResponse(
            CartDeliveryResource::make([
                'cart_info' => $cartInfo,
                'past_delivery_address' => $pastDeliveryAddress,
                'delivery_times' => $deliveryTimes,
                'industries' => $industries,
                'states' => $states,
                'given_zip_code' => $givenZipCode ?? '',
                'pickup_zipcode' => $pickupZipcode,
                'pickup_cafes' => $pickupCafes,
                'display_wc_personalised_msg' => $displayWcPersonalisedMsg,
                'existing_delivery_address_count' => $existingDeliveryAddressCount,
                'disable_dates' => $disable_dates,
                'allow_weekend_orders' => $allowWeekendOrders,
            ]),
            'Success'
        );
    }

    /**
     * Display the serving options page
     *
     * Shows available serving ware options based on cart contents and category requirements.
     * Allows customers to select paper products and serving utensils for their order.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function servingOptions()
    {
        $cartInfo = app(CartManager::class)->getActiveCart();

        if (!$cartInfo) {
            return $this->errorResponse('Your bag is empty, please add items to your bag', 400);
        }

        if (!$cartInfo->isGroupOrder() && $cartInfo->items && $cartInfo->items->count() == 0) {
            return $this->errorResponse('Your bag is empty, please add items to your bag', 400);
        }

        $ids = getCartItemCategoryServingTags();

        // Get all serving ware options
        $servingOptions = ServingOption::whereIn('id', $ids)->where('status', 'active')->get();

        // Get saved serving ware option from offmenu table having cart_id = $cartInfo->id
        $existingServingOption = Offmenu::where('cart_id', $cartInfo->id)->first();

        // Get paper products from shipping table
        $paper_products =
            $cartInfo->shipping && $cartInfo->shipping->paper_products == 0 ? '' : $cartInfo->shipping->paper_products;

        return $this->successResponse(
            CartServingOptionsResource::make([
                'serving_options' => $servingOptions,
                'existing_serving_option' => $existingServingOption,
                'paper_products' => $paper_products,
            ]),
            'Success'
        );
    }

    /**
     * Display the payment details page
     *
     * Shows payment options, saved payment methods, tip calculations, and rewards.
     * Handles both authenticated user and guest checkout scenarios with validation.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function payment()
    {
        // Validate cart and required information before showing payment
        $cartInfo = app(CartManager::class)->getActiveCart();
        if (!$cartInfo) {
            return $this->errorResponse('Cart not found.', 400);
        }
        if ($cartInfo->items->count() == 0) {
            return $this->errorResponse('Your cart is empty', 400);
        }
        if (!$cartInfo->shipping) {
            return $this->errorResponse('Please enter your delivery address', 400);
        }

        $companyPayments = UserManager::getUserPayment();
        $anetProfileExist = false;
        $amazonRewardBalance = 0;
        $amazonRewardApplied = 0;
        $amazonRewardMinSpendAmount = Setting::value('amazon_reward_min_spend');

        if (Auth::user()) {
            $user = Auth::user();
            $cim = Cim::where('alonti_user_id', $user->id)->with('paymentProfiles')->get();
            $cim = Cim::where('alonti_user_id', $user->id)->pluck('profile_id');
            $paymentProfileDetails = $cim
                ? CimPaymentProfile::whereIn('profile_id', $cim)
                    ->where(['delete_status' => 0, 'is_display' => 1])
                    ->get()
                : collect([]);
            $anetProfiles = $cim
                ? CimPaymentProfile::whereIn('profile_id', $cim)
                    ->where(['delete_status' => 1, 'gateway_name' => 'ANET'])
                    ->get()
                : collect([]);
            $anetProfileExist = $anetProfiles->count() > 0 ? true : false;
            // fetch amazon reward balance
            $amazonRewardBalance = app(Reward::class)->userCashOutAmount($user->id);
            // fetch amazon_reward_min_spend column value from settings table
            // fetch amazon reward applied at checkout if applicable
            $amazonRewardApplied = $cartInfo->amazon_reward_applied;
        } else {
            $paymentProfileDetails = $cartInfo
                ->paymentProfile()
                ->where(['delete_status' => 0, 'is_display' => 1])
                ->get();
        }

        $states = State::pluck('name', 'id');
        $states->prepend(' -- Select state -- ', '');
        $tipOptions = CartManager::getTipOption($cartInfo);

        $ccId = $poId = $codId = '';

        $companyPayments->map(function ($payment) use (&$ccId, &$poId, &$codId) {
            if ($payment->terms == 'Credit Card - Payment On Delivery') {
                $ccId = $payment->id;
            } elseif ($payment->terms == 'Purchase Order Only') {
                $poId = $payment->id;
            } elseif ($payment->terms == 'Cash (C.O.D)') {
                $codId = $payment->id;
            }
        });

        $cafeInfo = session()->get('UserDeliveryInformation.alontiDeliveryArea');
        $giftToDisplayValidation = CartManager::checkCartHasOnlyWarmCookie($cartInfo);
        $giftToDisplay = $giftToDisplayValidation['giftToDisplay'];
        $defaultTipAmount = config('custom.payment.tip.default');
        $customerOptAlontiRewardsEver = false;
        $customerOptAlontiRewardsEverEmailExist = false;

        if (Auth::user()) {
            $customerOptAlontiRewardsEver =
                Auth::user()->myconfig && Auth::user()->myconfig->alonti_rewards ? true : false;
            $customerOptAlontiRewardsEverEmailExist =
                Auth::user()->myconfig && Auth::user()->myconfig->reward_email != '' ? true : false;
        }

        if (($cartInfo->gift_card_rewards || $customerOptAlontiRewardsEver) && !$cartInfo->coupon_id) {
            $discountOptions = ['rewards' => 'Your current order rewards'];
        } else {
            $discountOptions = ['rewards' => 'Click to receive Alonti Rewards'];
        }

        $rewardConfigVal = Configuration::where([
            'column_key' => 'reward_type',
        ])->first();

        $rewardCalculateValue = $rewardConfigVal->field_value;

        if ($cartInfo->user_id && $cartInfo->abandonedCart && !$cartInfo->abandonedCart->alonti_user_id) {
            $cartInfo->abandonedCart->alonti_user_id = $cartInfo->user_id;
            $cartInfo->abandonedCart->update();
        }

        return $this->successResponse(
            CartPaymentResource::make([
                'cart_info' => $cartInfo,
                'cafe_info' => $cafeInfo,
                'company_payments' => $companyPayments,
                'payment_profile_details' => $paymentProfileDetails,
                'states' => $states,
                'tip_options' => $tipOptions,
                'cc_id' => $ccId,
                'po_id' => $poId,
                'cod_id' => $codId,
                'gift_to_display' => $giftToDisplay,
                'default_tip_amount' => $defaultTipAmount,
                'discount_options' => $discountOptions,
                'customer_opt_alonti_rewards_ever' => $customerOptAlontiRewardsEver,
                'reward_calculate_value' => $rewardCalculateValue,
                'amazon_reward_min_spend_amount' => $amazonRewardMinSpendAmount,
                'amazon_reward_balance' => $amazonRewardBalance,
                'amazon_reward_applied' => $amazonRewardApplied,
                'customer_opt_alonti_rewards_ever_email_exist' => $customerOptAlontiRewardsEverEmailExist,
                'anet_profile_exist' => $anetProfileExist,
            ]),
            'Success'
        );
    }

    /**
     * Display the order review page
     *
     * Final step before order placement. Shows complete order summary including
     * items, delivery details, payment info, and serving options for confirmation.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function review()
    {
        $cartInfo = app(CartManager::class)->getActiveCart();

        if (!$cartInfo) {
            return $this->errorResponse('Cart not found.', 400);
        }

        if ($cartInfo->items->count() == 0) {
            return $this->errorResponse('Your cart is empty', 400);
        }

        if (!$cartInfo->shipping) {
            return $this->errorResponse('Please enter your delivery address', 400);
        }

        if (!$cartInfo->billing) {
            return $this->errorResponse('Please enter your payment information', 400);
        }

        // Get serving ware options from offmenu table
        $servingOption = Offmenu::with('servingOption')->where('cart_id', $cartInfo->id)->first();

        $states = State::pluck('name', 'id')->toArray();
        $deliveryTimes = Time::pluck('time', 'id')->toArray();
        $payments = Payment::pluck('terms', 'id')->toArray();
        $items = $cartInfo->items();
        $items = $items->withoutAddons()->with('addons')->get();
        $cartInfo->shipping->delivery_date = $cartInfo->shipping->delivery_date
            ? Carbon::createFromFormat('Y-m-d', $cartInfo->shipping->delivery_date)->format('m/d/Y')
            : '';

        return $this->successResponse(
            CartReviewResource::make([
                'cart_info' => $cartInfo,
                'items' => $items,
                'states' => $states,
                'delivery_times' => $deliveryTimes,
                'payments' => $payments,
                'serving_option' => $servingOption,
            ]),
            'Success'
        );
    }

    /**
     * Add an item to the shopping cart
     *
     * Handles adding products to cart with validation, availability checking,
     * and cart creation if needed. Supports both individual and group orders.
     *
     * @param  ZipManager  $zipManager  Service for ZIP code validation
     * @param  CartAddRequest  $request  Validated request containing item details
     * @return \Illuminate\Http\JsonResponse JSON response with success/failure status
     */
    public function add(ZipManager $zipManager, CartAddRequest $request)
    {
        // Get the current active cart
        $cart = app(CartManager::class)->getActiveCart();

        // Check if cart belongs to a completed order - prevent modifications
        if ($cart && isset($cart->order) && in_array($cart->order->status, ['Delivered', 'Canceled'])) {
            // Clear active cart ID for user if order is complete
            if ($cart->user) {
                $user = $cart->user;
                $user->fresh();
                $user->active_cart_id = null;
                $user->save();
            }

            // Deactivate the cart
            $cart->status = 0;
            $cart->save();

            return response()->json([
                'status' => false,
                'message' => 'The order has been placed, hence you can not add the item to cart',
            ]);
        } else {
            $input = $request->all();

            // Check if the item is available and can be added to cart
            $itemAvailability = app(CartManager::class)->itemAvailability($cart, $input);

            if (!$itemAvailability['status']) {
                return response()->json(['status' => false, 'message' => $itemAvailability['msg']]);
            } else {
                // Create new cart if none exists
                if (empty($cart)) {
                    $cart = CartManager::createCart($zipManager);
                    if ($cart) {
                        User::updateActiveCartId($cart);
                    }
                }

                if ($cart) {
                    // Create new cart item and save it using CartManager
                    $cartItem = new CartItem();
                    $cartItem = CartManager::saveCartItem($request, $cart, $cartItem);

                    if ($cartItem) {
                        CartManager::createCartItemOptions($cartItem, $cart->state_id, $request->input('cartOptions'));
                        CartManager::createCartAddons($cart, $cartItem, $request->input('addons'));

                        if ($cart->coupon) {
                            app(UpdateCoupon::class)->calculateItemDiscount($cartItem);
                        }

                        $cart->calculateAndUpdate();
                        $result['count'] = $cart->getCartCount($cart);
                        $result['category'] = Category::getCategoryUrl($cartItem->category_id);

                        return response()->json(['status' => true, 'result' => $result]);
                    }
                }

                return response()->json(['status' => false, 'message' => 'Failed to add product to cart']);
            }
        }
    }

    /**
     * Update an existing cart item
     *
     * Modifies cart item quantity, options, and add-ons while preserving
     * discount and free item information. Recalculates totals and applies coupons.
     *
     * @param  CartUpdateRequest  $request  Validated request with updated item details
     * @return \Illuminate\Http\JsonResponse JSON response with success/failure status
     */
    public function update(CartUpdateRequest $request)
    {
        $id = $request->input('id');
        $cartItem = CartItem::find($id);
        $cart = app(CartManager::class)->getActiveCart();

        if ($cart && $cart->order && in_array($cart->order->status, ['Delivered', 'Canceled'])) {
            if ($cart->user) {
                $user = $cart->user;
                $user->fresh();
                $user->active_cart_id = null;
                $user->save();
            }

            $cart->status = 0;
            $cart->save();

            return response()->json([
                'status' => false,
                'message' => 'The order has been placed, hence you can not update the item to cart',
            ]);
        } else {
            if ($cart && $cartItem->cart_id == $cart->id) {
                $input = $request->all();
                $input['discount'] = $cartItem->discount;
                $input['free_item_id'] = $cartItem->free_item_id;
                $input['is_free_item'] = $cartItem->is_free_item;
                $cartItem = CartManager::saveCartItem($input, $cart, $cartItem);

                if ($cartItem) {
                    CartOption::deleteByCartItemId($cartItem->id);
                    CartManager::createCartItemOptions($cartItem, $cart->state_id, $request->input('cartOptions'));
                    $cartItem->deleteAddons($cartItem->id);
                    CartManager::createCartAddons($cart, $cartItem, $request->input('addons'));
                    if ($cart->coupon) {
                        app(UpdateCoupon::class)->calculateItemDiscount($cartItem);
                    }
                    $cart->calculateAndUpdate();

                    return response()->json(['status' => true]);
                }

                return response()->json(['status' => false, 'message' => 'Failed to update Cart Item']);
            } else {
                return response()->json(['status' => false, 'message' => 'Cart does not exists']);
            }
        }
    }

    /**
     * Save default meal for pending group order invitees
     *
     * Creates cart items with the configured default meal for invitees who haven't
     * responded by the deadline. Removes any existing items and adds the default.
     *
     * @param  int  $id  Cart ID
     * @param  array  $pendingInviteeIds  Array of invitee IDs who need default meals
     * @return bool Success status
     */
    public function saveInviteeDefaultMeal($id, $pendingInviteeIds)
    {
        $cart = Cart::where(['id' => $id])
            ->with([
                'groupOrderConfig',
                'invitees' => function ($q) {
                    return $q->whereIn('response', [1, 2])->with('invitee');
                },
            ])
            ->first();

        $cartOptions = [];

        if ($cart->groupOrderConfig->options_selection_id) {
            $options = json_decode($cart->groupOrderConfig->options_selection_id);
            $i = 0;

            foreach ($options as $val) {
                $explVal = explode('-', $val);
                $cartOptions[$i]['product_option_id'] = intval($explVal[0]);
                $cartOptions[$i]['product_selection_id'] = intval($explVal[1]);
            }
        }

        foreach ($cart->invitees as $invitee) {
            if (in_array($invitee->invitee_id, $pendingInviteeIds)) {
                $defaultMeal = [
                    'invitee_default_meal' => true,
                    'product_id' => $cart->groupOrderConfig->product_id,
                    'product_variant_id' => $cart->groupOrderConfig->variant_id,
                    'quantity' => 1,
                    'special_instruction' => null,
                    'tags' => [['text' => $invitee->invitee->email]],
                    'cartOptions' => $cartOptions,
                    'addons' => [],
                    'invitee_id' => $invitee->invitee_id,
                ];
                $cartItem = new CartItem();
                $pendingAddedCartItems = CartItem::where([
                    'cart_id' => $cart->id,
                    'invitee_id' => $invitee->invitee_id,
                    'addon_cartitem_id' => null,
                ])->get();
                if ($pendingAddedCartItems->count() > 0) {
                    $pendingAddedCartItems->each(function (CartItem $cartItem) {
                        $cartItem->deleteItem();
                    });
                }
                $cartItem = new CartItem();
                $cartItem = CartManager::saveCartItem($defaultMeal, $cart, $cartItem);
                if ($cartItem) {
                    CartManager::createCartItemOptions($cartItem, $cart->state_id, $defaultMeal['cartOptions']);
                    if ($cart->coupon) {
                        app(UpdateCoupon::class)->calculateItemDiscount($cartItem);
                    }
                    $invitee->response = CartInvitee::RESPONSE_COMPLETED;
                    $invitee->save();
                } else {
                }
            }
        }

        $cart->calculateAndUpdate();

        return true;
    }
}
