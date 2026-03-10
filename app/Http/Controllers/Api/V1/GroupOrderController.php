<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Alonti\Cart\CartManager;
use App\Alonti\Invitation\InvitationManager;
use App\Alonti\View\OrderGroupSelect;
use App\Alonti\ZipManager\ZipManager;
use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Models\CartInvitee;
use App\Models\Category;
use App\Models\GroupOrder;
use App\Models\GroupOrderConfiguration;
use App\Models\Invitee;
use App\Models\Product;
use App\Models\Shipping;
use App\Models\State;
use App\Models\User;
use App\Models\Zipcode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class GroupOrderController extends Controller
{
    public function start()
    {
        if (Auth::user()) {
            $cartInfo = app(CartManager::class)->getActiveCart();
            if ($cartInfo && $cartInfo->order) {
                return redirect('/summary')->with(
                    'notify-failure',
                    'Some of your order is edit mode. Please complete and start your group order'
                );
            }
            $individualCart = Cart::individual()->mine()->pending()->first();

            if ($individualCart) {
                // $individualCart->discardCart();
            }

            return redirect('/group-order/invite-to-order');
        } else {
            return redirect('/group-order/login');
        }
    }

    public function login()
    {
        $socialLoginSettings = DB::select('select * from settings')[0];

        return view('group-order.login', ['isGroupOrder' => true, 'socialLoginSettings' => $socialLoginSettings]);
    }

    public function invitePeople()
    {
        return redirect('/group-order/start');
    }

    public function createInviteList()
    {
        $groupDetails = request()->only('groupName', 'emails', 'groupId', 'notes', 'cart_id');
        if (isset($groupDetails['emails']) && !empty($groupDetails['emails'])) {
            $groupDetails['emails'] = array_unique($groupDetails['emails'], SORT_REGULAR);
        }
        $inviteesArray = [];
        $send = false;
        if (request()->has('groupId')) {
            if (isset($groupDetails['cart_id'])) {
                $cartInfo = Cart::find($groupDetails['cart_id']);
                $groupOrder = GroupOrder::getGroupOrderDetails($cartInfo)->find($groupDetails['groupId']);
                $send = true;
            } else {
                $groupOrder = GroupOrder::find($groupDetails['groupId']);
            }
            $zipManager = new ZipManager();
            $inviteesArray = [
                'groupNameSelected' => $groupDetails['groupId'],
                'selectedEmails' => $groupDetails['emails'],
                'notes' => isset($groupDetails['notes']) ? $groupDetails['notes'] : '',
                'cart_id' => isset($groupDetails['cart_id']) ? $groupDetails['cart_id'] : '',
            ];
        } else {
            $alreadyExists = auth()->user()->group_orders()->where('name', $groupDetails['groupName'])->exists();
            if ($alreadyExists) {
                return [
                    'success' => false,
                    'message' => "There is already a group named '{$groupDetails['groupName']}'. Please provide a different name.",
                ];
            }
            $groupOrder = GroupOrder::create([
                'user_id' => Auth::user()->id,
                'name' => $groupDetails['groupName'],
                'notes' => '',
            ]);
        }
        $additionalMsg = '';
        $invitees = collect($groupDetails['emails'])
            ->map(function ($invitee) use ($groupOrder, &$additionalMsg) {
                $isInviteeExists = Invitee::where('group_order_id', $groupOrder->id)
                    ->where('email', $invitee['email'])
                    ->first();
                if ($isInviteeExists) {
                    $additionalMsg = 'Please add unique email address only.';

                    return true;
                }
                $invitee = new Invitee([
                    'name' => $invitee['name'] ? $invitee['name'] : '',
                    'email' => $invitee['email'],
                    'group_order_id' => $groupOrder->id,
                ]);

                return $groupOrder->invitees()->save($invitee);
            })
            ->filter(function ($value, $key) {
                return is_object($value);
            });

        if ($inviteesArray && $send && $invitees->count()) {
            $inviteesArray['selectedEmails'] = $invitees;
            $this->sendInvitations($zipManager, $inviteesArray);
        }
        Session::flash('notify-success', "Group '{$groupOrder->name}' has been updated successfully." . $additionalMsg);

        return ['success' => true, 'gid' => $groupOrder->id];
    }

    public function sendInvitations(ZipManager $zipManager, $invitees = [])
    {
        // Validate user authentication
        if (!Auth::user()) {
            Session::flash('notify-success', 'Your session logged out.');

            return ['success' => true, 'redirectTo' => '/login'];
        }

        $cartRecord = [];
        if (!$invitees) {
            $invitees = request('invitees');
        }

        $deliveryArea = session()->get('UserDeliveryInformation.alontiDeliveryArea');
        $state_id = $zipManager->getDeliveryZipcodeStateId();
        $groupOrder = GroupOrder::find($invitees['groupNameSelected']);

        $cartRecord = [
            'cafe_id' => $deliveryArea->cafe->id,
            'user_id' => Auth::user()->id,
            'group_order_id' => $invitees['groupNameSelected'],
            'zipcode' => session()->get('UserDeliveryInformation.givenZipCode'),
            'state_id' => $state_id,
            'order_name' => $groupOrder->name,
            'group_order_notes' => $invitees['notes'],
        ];

        if (!isset($invitees['cart_id']) || !$invitees['cart_id']) {
            $cart = Cart::create($cartRecord);
            $data = [
                'cart_id' => $cart->id,
                'alonti_user_id' => Auth::user()->id,
                'cafe_id' => $deliveryArea->cafe->id,
            ];
            AbandonedCart::create($data);
            $input = request()->all();
            $this->groupOrderConfig($input, $cart);
        } else {
            $cart = Cart::find($invitees['cart_id']);
        }

        $ci = collect($invitees['selectedEmails'])->transform(function ($invitee) use ($cart, $invitees) {
            $invitee_id = is_object($invitee) ? $invitee->id : $invitee;
            $cartInvitee = new CartInvitee([
                'invitee_id' => $invitee_id,
                'response' => CartInvitee::RESPONSE_PENDING,
                'group_order_id' => $invitees['groupNameSelected'],
            ]);
            $cart->invitees()->save($cartInvitee);

            return $cartInvitee;
        });

        $invitationIds = $ci->pluck('id')->toArray();
        $group = GroupOrder::find($invitees['groupNameSelected']);

        $cart->group_order_notes = $invitees['notes'];
        $group->save();
        $cart->mailer()->sendInvitationToInvitees($invitationIds);
        $cart->mailer()->sendGroupOrderNotificationToCsm();
        Session::flash(
            'notify-success',
            'Invitation has been sent to invitees. Please check your cart to get the updates.'
        );

        return ['success' => true, 'redirectTo' => '/'];
    }

    /**
     * Display group order invitation configuration page
     *
     * Shows form for configuring group order settings including delivery details,
     * response deadlines, budget limits, and default meal options. Handles both
     * new group orders and editing existing configurations.
     *
     * @param  ZipManager  $zipManager  Service for delivery area management
     * @return \Illuminate\View\View
     */
    public function inviteToOrder(ZipManager $zipManager)
    {
        // Ensure user is authenticated
        if (!Auth::user()) {
            return redirect('/login');
        }
        // Initialize configuration variables from request parameters
        $group_order_id = request('gid');
        $configId = request('gcid');
        $cartId = request('cid');
        $updateGroupOrderConfig = false;
        $configData = [];
        $shippingData = [];
        $choosenDeliveryDate = null;
        $choosenResponseDate = null;
        $inviteeDefaultMeal = null;
        $cartInfo = null;
        $optionSelection = [];
        $edit = false;
        // Handle editing existing group order configuration
        if ($configId && $cartId && $group_order_id) {
            $edit = true;
            $updateGroupOrderConfig = true;

            // Load existing configuration and cart data
            $configData = GroupOrderConfiguration::where(['id' => $configId])->first();
            $cartInfo = Cart::find($cartId);
            $shippingData = $cartInfo->shipping;

            // Format dates for display
            $choosenDeliveryDate = Carbon::createFromFormat('Y-m-d', $shippingData->delivery_date)->format('m/d/Y');
            $choosenResponseDate = Carbon::createFromFormat('Y-m-d', $configData->response_date)->format('m/d/Y');

            // Load default meal configuration if set
            if ($configData->product_id) {
                $inviteeDefaultMeal = Product::getProductById($configData->product_id, $cartInfo->state_id);
                $optionSelection = json_decode($configData->options_selection_id);
            }

            // Get existing invitee IDs for this cart
            $cartInviteeIds = CartInvitee::where([
                'cart_id' => $cartId,
            ])->pluck('invitee_id');
            view()->share(compact('cartId', 'configId', 'group_order_id', 'cartInviteeIds'));
        } else {
            // Handle new group order creation - clear active cart if it's a group order
            $cart = Auth::user()->active_cart_id ? Cart::find(Auth::user()->active_cart_id) : null;
            if ($cart && $cart->group_order_id) {
                $user = User::find(Auth::user()->id);
                $user->active_cart_id = null;
                $user->save();
            }
        }
        // Share configuration data with view
        view()->share(
            compact(
                'configData',
                'shippingData',
                'choosenDeliveryDate',
                'choosenResponseDate',
                'updateGroupOrderConfig',
                'inviteeDefaultMeal',
                'optionSelection',
                'cartInfo',
                'edit'
            )
        );

        // Get user's group orders and prepare for display
        $group_orders = Auth::user()->group_orders;
        $group_orders = new OrderGroupSelect($group_orders, true);

        // Get delivery state and invitee-accessible categories/products
        $state_id = $zipManager->getDeliveryZipcodeStateId();
        $inviteeCategories = Category::getInviteeCategories(true, $state_id);
        $inviteeProducts = [];
        $productPrices = [];
        // Extract products and prices from invitee categories
        $inviteeCategories->map(function (Category $category) use (&$inviteeProducts, &$productPrices) {
            if ($category->products->count() > 0) {
                $category->products->map(function (Product $product) use (&$inviteeProducts, &$productPrices) {
                    $product->touch(); // Update product timestamp
                    $productPrices[] = $product->price;
                    $inviteeProducts[] = $product;
                });
            }
        });
        // Calculate price ranges for budget configuration
        $minProductPrice = !empty($productPrices) ? min($productPrices) : 0;
        $maxProductPrice = !empty($productPrices) ? max($productPrices) : 0;

        // Get configuration options from config files
        $inviteeResponseTimes = config('custom.inviteeResponseTime');
        $inviteeBudget = config('custom.inviteeBudget');
        $maxDefaultBudget = max(array_keys(config('custom.inviteeBudget')));

        // Prepare form dropdown options
        $states = State::where('status', 1)->pluck('name', 'id');
        $states->prepend(' -- Select state -- ', '');
        $previousGroup = $group_orders->items->count() > 0 ? true : false;
        // Configure delivery times based on market settings
        $deliveryTimes = config('custom.delivery_pickup_time');

        // Get zipcode with market information
        $zipCode = Zipcode::where('zipcode', session()->get('UserDeliveryInformation.givenZipCode'))
            ->with('cafe', 'cafe.market')
            ->first();

        // Use extended hours if market allows night orders
        if ($zipCode && $zipCode->cafe->market->allow_night_orders == 1) {
            $deliveryTimes = config('custom.day_night_delivery_pickup_time');
        }

        // Check weekend order availability for this market
        $allowWeekendOrders =
            $zipCode && $zipCode->cafe && $zipCode->cafe->market && $zipCode->cafe->market->allow_weekend_orders == 1
                ? true
                : false;

        return view(
            'group-order.invite-to-order',
            compact(
                'group_order_id',
                'group_orders',
                'deliveryTimes',
                'inviteeResponseTimes',
                'inviteeProducts',
                'inviteeBudget',
                'minProductPrice',
                'maxDefaultBudget',
                'maxProductPrice',
                'configData',
                'shippingData',
                'states',
                'previousGroup',
                'allowWeekendOrders'
            )
        );
    }

    /**
     * Fetch invitee list for a group order
     *
     * Returns all invitees associated with a specific group order.
     * Used for populating invitee selection interfaces.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function fetchInviteeList()
    {
        $group_order_id = request('gid');
        $invitees = Invitee::where('group_order_id', $group_order_id)->get();

        return $invitees;
    }

    /**
     * Send group order invitation with delivery validation
     *
     * Validates delivery date and time constraints, creates cart and configuration,
     * associates invitees, and sends invitation emails. Includes weekend and
     * night order validation based on market settings.
     *
     * @param  ZipManager  $zipManager  Service for delivery area management
     * @param  array  $invitees  Array of invitee data and configuration
     * @return array JSON response with success/error status
     */
    public function sendInvitation(ZipManager $zipManager, $invitees = [])
    {
        // Validate delivery date and weekend restrictions
        $deliveryDate = date('Y-m-d', strtotime(request()->all()['delivery_date']));
        $isWeekend = date('N', strtotime($deliveryDate)) >= 6; // Check if Saturday/Sunday

        $zipCode = Zipcode::where('zipcode', request()['delivery']['zipcode'])
            ->with('cafe', 'cafe.market')
            ->first();

        // Validate weekend delivery is allowed for this market
        if ($zipCode->cafe->market->allow_weekend_orders == 0 && $isWeekend) {
            return [
                'success' => false,
                'message' => 'Currently, we are not allowing weekend orders for the specified zip code. Please choose a different delivery date that is not on the weekend.',
            ];
        }

        // Validate night order time restrictions
        $nightOrderTimeSlots = [68, 26, 69, 27, 70, 28, 71, 29, 72, 30, 73, 31, 74, 32]; // Time slots after 4:30 PM
        $deliveryTimeSelected = request()->all()['delivery_time'];

        // Check if night delivery is allowed for this market
        if ($zipCode->cafe->market->allow_night_orders == 0 && in_array($deliveryTimeSelected, $nightOrderTimeSlots)) {
            return [
                'success' => false,
                'message' => 'Currently, we are not available to delivery after 4:30 PM for the specified zip code. Please select a delivery time before 4:30 PM.',
            ];
        }

        if (!Auth::user()) {
            Session::flash('notify-success', 'Your session logged out.');

            return ['success' => true, 'redirectTo' => '/login'];
        }
        $cartRecord = [];
        if (!$invitees) {
            $invitees = request('invitees');
        }
        // $zipManager->setDeliveryAreaByZip(session()->get('UserDeliveryInformation.givenZipCode'));
        $deliveryArea = session()->get('UserDeliveryInformation.alontiDeliveryArea');
        $state_id = $zipManager->getDeliveryZipcodeStateId();
        $groupOrder = GroupOrder::find($invitees['groupNameSelected']);
        $cartRecord = [
            'cafe_id' => $deliveryArea->cafe->id,
            'user_id' => Auth::user()->id,
            'group_order_id' => $invitees['groupNameSelected'],
            'zipcode' => session()->get('UserDeliveryInformation.givenZipCode'),
            'state_id' => $state_id,
            'order_name' => $groupOrder->name,
            'group_order_notes' => $invitees['notes'],
        ];
        if (!isset($invitees['cart_id']) || !$invitees['cart_id']) {
            $cart = Cart::create($cartRecord);
            $data = [
                'cart_id' => $cart->id,
                'alonti_user_id' => Auth::user()->id,
                'cafe_id' => $deliveryArea->cafe->id,
            ];
            AbandonedCart::create($data);
            $input = request()->all();
            $this->groupOrderConfig($input, $cart);
        } else {
            $cart = Cart::find($invitees['cart_id']);
        }
        $ci = collect($invitees['selectedEmails'])->transform(function ($invitee) use ($cart, $invitees) {
            $invitee_id = is_object($invitee) ? $invitee->id : $invitee;
            $cartInvitee = new CartInvitee([
                'invitee_id' => $invitee_id,
                'response' => CartInvitee::RESPONSE_PENDING,
                'group_order_id' => $invitees['groupNameSelected'],
            ]);
            $cart->invitees()->save($cartInvitee);

            return $cartInvitee;
        });
        $invitationIds = $ci->pluck('id')->toArray();
        $group = GroupOrder::find($invitees['groupNameSelected']);

        $cart->group_order_notes = $invitees['notes'];
        $group->save();
        $cart->mailer()->sendInvitationToInvitees($invitationIds);
        $cart->mailer()->sendGroupOrderNotificationToCsm();
        Session::flash(
            'notify-success',
            'Invitation has been sent to invitees. Please check your cart to get the updates.'
        );

        return ['success' => true, 'redirectTo' => '/'];
    }

    /**
     * Configure group order settings and shipping
     *
     * Creates group order configuration record with response deadlines,
     * budget limits, and default meal settings. Also creates shipping record.
     *
     * @param  array  $input  Request input data with configuration settings
     * @param  Cart  $cart  Cart instance to configure
     * @return void
     */
    public function groupOrderConfig($input, $cart)
    {
        // Prepare group order configuration data
        $data = [
            'cart_id' => $cart->id,
            'cafe_id' => $cart->cafe_id,
            'group_order_id' => $cart->group_order_id,
            'response_date' => Carbon::createFromFormat('m/d/Y', $input['response_date'])->format('Y-m-d'),
            'response_time' => $input['response_time'],
            'invitee_budget' => $input['budgetAmount'] ? $input['budgetAmount'] : null,
            'default_meal' => $input['defaultMeal'],
            'category_id' => $input['defaultMeal'] == '1' ? $input['categoryId'] : null,
            'product_id' => $input['defaultMeal'] == '1' ? $input['productId'] : null,
            'variant_id' => $input['defaultMeal'] == '1' ? $input['variantId'] : null,
            'options_selection_id' => $input['defaultMeal'] == '1' ? json_encode($input['optionIds']) : null,
        ];

        // Create group order configuration
        $result = GroupOrderConfiguration::create($data);

        // Prepare and create shipping information
        $shippingData = [
            'cart_id' => $cart->id,
            'delivery_date' => Carbon::createFromFormat('m/d/Y', $input['delivery_date'])->format('Y-m-d'),
            'delivery_time' => $input['delivery_time'],
            'address1' => isset($input['delivery']['address']) ? $input['delivery']['address'] : null,
            'address2' => isset($input['delivery']['address_two']) ? $input['delivery']['address_two'] : null,
            'city' => isset($input['delivery']['city']) ? $input['delivery']['city'] : null,
            'state' => isset($input['delivery']['stateSelected']) ? $input['delivery']['stateSelected'] : null,
            'zipcode' => isset($input['delivery']['zipcode']) ? $input['delivery']['zipcode'] : null,
        ];
        $createShipping = Shipping::create($shippingData);
    }

    public function saveGroupName()
    {
        $req = request('group');
        $group = GroupOrder::find($req['value']);
        $group->name = $req['text'];
        $group->save();

        return ['success' => true, 'message' => 'Group name has been updated'];
    }

    public function deleteGroupName()
    {
        if (request()->has('groupOrderId')) {
            $id = request()->get('groupOrderId');
            $group = GroupOrder::find($id);
            $carts = Cart::where(['group_order_id' => $group->id])->get();
            $pendingCart = false;
            foreach ($carts as $key => $value) {
                if (!$value->order_id) {
                    $pendingCart = true;
                    break;
                } elseif (
                    $value->order &&
                    ($value->order->status != 'Delivered' || $value->order->status != 'Canceled')
                ) {
                    $pendingCart = true;
                    break;
                }
            }
            if (!$pendingCart) {
                if ($group->delete()) {
                    Session::flash('notify-success', 'Group name has been deleted successfully.');

                    return ['success' => true, 'message' => 'Group name has been deleted'];
                } else {
                    return ['success' => false, 'message' => 'Group name has not been deleted'];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'You can delete the group if you dont have any pending cart or order',
                ];
            }
        } else {
            return ['success' => false, 'message' => 'Group name has not been deleted'];
        }
    }

    public function removeInvitee()
    {
        if (request()->has('editCartId') && request()->has('invitee')) {
            $invitee = request('invitee');
            $editCartId = request('editCartId');
            $group_order = GroupOrder::find($invitee['group_order_id']);
            $inviteeCheck = CartInvitee::where([
                'invitee_id' => $invitee['id'],
                'cart_id' => $editCartId,
                'group_order_id' => $invitee['group_order_id'],
            ])->first();
            if ($inviteeCheck) {
                CartInvitee::where([
                    'invitee_id' => $invitee['id'],
                    'cart_id' => $editCartId,
                ])->delete();
                $cartInviteeIds = CartInvitee::where([
                    'cart_id' => $editCartId,
                ])->pluck('invitee_id');

                return [
                    'success' => true,
                    'message' => 'Invitee has been removed from this group order',
                    'cartInviteeIds' => $cartInviteeIds,
                ];
            } else {
                return ['success' => false, 'message' => 'This invitee is not associated with this order.'];
            }
        } else {
            return ['success' => false, 'message' => 'Empty params'];
        }
    }

    /**
     * Handle group order invitation decline
     *
     * Processes invitee declining a group order invitation. Validates invitation
     * status, updates response, and sends notification to group leader.
     *
     * @param  string  $hashid  Encrypted cart invitee ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function decline($hashid)
    {
        // Find and validate invitation status
        $cartInvitee = CartInvitee::findByEncryptedId($hashid);
        if ($cartInvitee && $cartInvitee->isOrdered()) {
            return redirect('/')->with(
                'notify-failure',
                'The order has been placed, for further information please contact your group leader.'
            );
        }

        // Process decline if invitation is still pending
        $cartInvitee = $cartInvitee->fresh();
        if ($cartInvitee && $cartInvitee->isPending()) {
            $cartInvitee->response = CartInvitee::RESPONSE_DECLINED;
            $cartInvitee->save();

            // Notify group leader of decline
            $cartInvitee->mailer()->sendDeclineNotification();

            return redirect('/')->with('notify-success', 'You have declined the invitation.');
        }

        return redirect('/')->with(
            'notify-failure',
            'Invitation has expired. Please contact the group leader for further assistance.'
        );
    }

    /**
     * Handle group order invitation acceptance
     *
     * Processes invitee accepting a group order invitation. Validates deadline,
     * updates response status, and creates invitation session for ordering.
     *
     * @param  InvitationManager  $invitationManager  Service for invitation session management
     * @param  string  $hashid  Encrypted cart invitee ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accept(InvitationManager $invitationManager, $hashid)
    {
        // Find and validate invitation
        $cartInvitee = CartInvitee::findByEncryptedId($hashid);
        if ($cartInvitee && $cartInvitee->isOrdered()) {
            return redirect('/')->with(
                'notify-failure',
                'The order has been placed, for further information please contact your group leader.'
            );
        }

        if ($cartInvitee) {
            // Check if response deadline has passed
            if ($cartInvitee->cart->groupOrderConfig) {
                $responseTime = strtotime(
                    $cartInvitee->cart->groupOrderConfig->response_date .
                        ' ' .
                        $cartInvitee->cart->groupOrderConfig->response_time
                );
                $timeZone = abs($cartInvitee->cart->cafe->market->timezone_difference);
                $timeZoneHours = strtotime('-' . $timeZone . ' hours');
                if ($timeZoneHours >= $responseTime) {
                    return redirect('/')->with(
                        'notify-failure',
                        'You missed the order deadline. Please contact your group leader if you need lunch.'
                    );
                }
            }
            // Accept invitation if valid
            $cartInvitee = $cartInvitee->fresh();
            if ($cartInvitee && ($cartInvitee->isPending() || $cartInvitee->hasAccepted())) {
                $cartInvitee->response = CartInvitee::RESPONSE_ACCEPTED;
                $cartInvitee->save();

                // Create invitation session for ordering
                $invitationManager->createSessionFor($cartInvitee);

                return redirect('/invitation')->with('notify-success', 'You can start adding items to cart now.');
            }

            return redirect('/')->with(
                'notify-failure',
                'The invite is no longer valid as you have completed your order.'
            );
        } else {
            return redirect('/')->with('notify-failure', 'The invite is no longer valid.');
        }
    }

    public function remindInvite(Request $request)
    {
        try {
            Cart::where('id', $request->get('cartId'))->update(['group_order_notes' => $request->get('notes')]);
            $invitationIds = $request->get('invitations');
            if ($invitationIds) {
                $cartInvitation = CartInvitee::whereIN('id', $invitationIds)->first();
                $cartInvitation->cart->mailer()->remindInvitationToInvitees($invitationIds);
            }

            return response()->json(['success' => true, 'message' => 'Reminder sent']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function resendInvite(Request $request)
    {
        try {
            $invitationIds = $request->get('invitations');
            if ($invitationIds) {
                foreach ($invitationIds as $key => $id) {
                    $cartInvitee = CartInvitee::find($id);
                    $cartInvitee->response = CartInvitee::RESPONSE_PENDING;
                    $cartInvitee->resent_invitation = 1;
                    $cartInvitee->save();
                }
                $cartInvitation = CartInvitee::whereIN('id', $invitationIds)->first();
                $cartInvitation->cart->mailer()->resendInvitationToInvitees($invitationIds);

                return response()->json(['success' => true, 'message' => 'Resent invitation']);
            }

            return response()->json(['message' => 'Please select any email']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function saveAndStartNewOrder()
    {
        auth()
            ->user()
            ->update(['active_cart_id' => null]);

        return redirect('order/start-group-order')->with(['notify-success' => 'Start a new group order']);
    }

    public function activateGroupOrder()
    {
        $user = auth()->user();
        if ($user && $user->activeGroupOrder()->count() == 1) {
            $individualCart = Cart::individual()->mine()->pending()->first();
            if ($individualCart) {
                $individualCart->discardCart();
            }
            $activeCart = $user->activeGroupOrder()->first();
            $user->update(['active_cart_id' => $activeCart->id]);
        }

        return redirect('summary');
    }

    public function updateGroupOrderInvitation()
    {
        if (!Auth::user()) {
            Session::flash('notify-success', 'Your session logged out.');

            return ['success' => true, 'redirectTo' => '/login'];
        }
        $input = request()->all();
        $groupOrderConfig = GroupOrderConfiguration::where('id', '=', $input['configId'])
            ->with(['cart', 'cart.shipping', 'cart.invitees', 'groupOrder'])
            ->first();
        if ($groupOrderConfig->cart->invitees) {
            $inviteeIds = $groupOrderConfig->cart->invitees->pluck('invitee_id')->toArray();
        }
        $nonInviteeIds = array_diff($input['invitees']['selectedEmails'], $inviteeIds);
        // dd($input, $nonInviteeIds, $inviteeIds, $groupOrderConfig->cart->invitees);

        $groupOrderConfig->cart->group_order_notes = $input['invitees']['notes'];
        $groupOrderConfig->cart->save();
        $data = [
            'response_date' => Carbon::createFromFormat('m/d/Y', $input['response_date'])->format('Y-m-d'),
            'response_time' => $input['response_time'],
            'invitee_budget' => $input['budgetAmount'],
            'default_meal' => $input['defaultMeal'],
            'category_id' => $input['defaultMeal'] == '1' ? $input['categoryId'] : null,
            'product_id' => $input['defaultMeal'] == '1' ? $input['productId'] : null,
            'variant_id' => $input['defaultMeal'] == '1' ? $input['variantId'] : null,
            'options_selection_id' => $input['defaultMeal'] == '1' ? json_encode($input['optionIds']) : null,
        ];
        $updateConfig = $groupOrderConfig->update($data);
        $shippingData = [
            'cart_id' => $groupOrderConfig->cart->id,
            'delivery_date' => Carbon::createFromFormat('m/d/Y', $input['delivery_date'])->format('Y-m-d'),
            'delivery_time' => $input['delivery_time'],
            'address1' => isset($input['delivery']['address']) ? $input['delivery']['address'] : null,
            'address2' => isset($input['delivery']['address_two']) ? $input['delivery']['address_two'] : null,
            'city' => isset($input['delivery']['city']) ? $input['delivery']['city'] : null,
            'state' => isset($input['delivery']['stateSelected']) ? $input['delivery']['stateSelected'] : null,
            'zipcode' => isset($input['delivery']['zipcode']) ? $input['delivery']['zipcode'] : null,
        ];
        $updateShipping = $groupOrderConfig->cart->shipping->update($shippingData);
        $gid = $groupOrderConfig->groupOrder->id;
        $cart = $groupOrderConfig->cart;
        $ci = collect($nonInviteeIds)->transform(function ($invitee) use ($cart, $gid) {
            $invitee_id = is_object($invitee) ? $invitee->id : $invitee;
            $cartInvitee = new CartInvitee([
                'invitee_id' => $invitee_id,
                'response' => CartInvitee::RESPONSE_PENDING,
                'group_order_id' => $gid,
            ]);
            $cart->invitees()->save($cartInvitee);

            return $cartInvitee;
        });
        $invitationIds = CartInvitee::where('cart_id', '=', $groupOrderConfig->cart->id)->pluck('id')->toArray();
        $updatedCart = Cart::find($groupOrderConfig->cart->id);
        $updatedCart->mailer()->sendInvitationToInvitees($invitationIds, true);
        $updatedCart->mailer()->sendGroupOrderNotificationToCsm('update');
        Session::flash(
            'notify-success',
            'Updated invitation has been sent. Please check your cart to get the updates.'
        );

        return ['success' => true, 'redirectTo' => '/'];
    }

    /**
     * Removes the unupdated invitee.
     */
    public function removeUnupdatedInvitee()
    {
        if (!request()->has('invitee')) {
            return ['success' => false, 'message' => 'Empty params'];
        }

        $invitee = request('invitee');
        $invitee = Invitee::where(['id' => $invitee['id']])->first();

        if ($invitee) {
            $invitee->deleted_at = Carbon::now();
            $invitee->save();

            return ['success' => true, 'message' => 'Invitee has been removed from this group order'];
        }

        return ['success' => false, 'message' => 'This invitee is not associated with this order.'];
    }
}
