<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Alonti\Cart\CartManager;
use App\Alonti\Coupon\CouponManager;
use App\Alonti\Coupon\ResetCoupon;
use App\Alonti\Coupon\UpdateCoupon;
use App\Alonti\Invitation\InvitationManager;
use App\Alonti\Order\OrderPlacement;
use App\Alonti\Order\StoreDelivery;
use App\Alonti\Order\StorePayment;
use App\Alonti\Payment\Drivers\AuthorizenetException;
use App\Alonti\PaymentService\PaymentService;
use App\Alonti\ZipManager\ZipManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CouponRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CimPaid;
use App\Models\CimPaymentProfile;
use App\Models\GroupOrder;
use App\Models\Offmenu;
use App\Models\Order;
use App\Models\ServingOption;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    /**
     * update
     *
     * @param  mixed  $request
     * @return void
     */
    public function update(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        $this->inviteeUpdate();
        if ($request->has('cartItemId') && $request->has('quantity') && session()->has('UserDeliveryInformation')) {
            $id = $request->get('cartItemId');
            $quantity = $request->get('quantity');
            $cartItem = CartItem::find($id);
            if (
                !session()->has('invitation') &&
                $cartItem &&
                ($cartItem->cart->user && !$cartItem->cart->user->active_cart_id)
            ) {
                $data['msg'] = 'Your cart is empty';
            } elseif (
                !session()->has('invitation') &&
                $cartItem &&
                $cartItem->cart->order &&
                in_array($cartItem->cart->order->status, ['Delivered', 'Canceled'])
            ) {
                if ($cartItem->cart->user) {
                    $user = $cartItem->cart->user;
                    $user->fresh();
                    $user->active_cart_id = null;
                    $user->save();
                }
                $cartItem->cart->status = 0;
                $cartItem->cart->save();
                $data['msg'] = 'The order has been placed, hence you can not update the item quantity';
            } elseif ($cartItem) {
                $data['cartId'] = $cartItem->cart_id;
                $result = $cartItem->updateQuantity($quantity);
                app(UpdateCoupon::class)->updateItemDiscount($cartItem);
                $result['item_count'] = app(CartManager::class)->getCartCount();
                if (!empty($result)) {
                    if ($cartItem->box_lunch_type == 1) {
                        $data['boxlunch_total'] = CartItem::getBoxLunchTotal($cartItem->cart_id);
                    }
                    $data['status'] = true;
                    $data['msg'] = 'Success';
                    $data['result'] = $result;
                    $data['groupOrder'] = [];
                    $data['itemCountInvitee'] = 0;
                    $data['inviteeSubTotal'] = 0;
                    if ($cartItem->cart->isGroupOrder()) {
                        $data['groupOrder'] = GroupOrder::getGroupOrderDetails($cartItem->cart)
                            ->where('id', $cartItem->cart->group_order_id)
                            ->first();
                        $itemCountInvitee = app(InvitationManager::class)->getInviteeCartCount();
                        $data['itemCountInvitee'] = $itemCountInvitee;
                        $data['inviteeSubTotal'] = $cartItem->cart->totalForInvitee(
                            session()->get('invitation.invitee_id')
                        );
                    }
                }
            } else {
                $data['msg'] = 'This item is not exist in the cart';
            }
        }

        return response()->json($data);
    }

    /**
     * inviteeUpdate
     *
     * @return void
     */
    public function inviteeUpdate()
    {
        if (session()->has('invitation.cart_invitee_id')) {
            config()->set('app.request-from-invitee', true);
            $invitationManager = app(InvitationManager::class);
            $code = $invitationManager->getCart()->zipcode;
            $zipManager = app(ZipManager::class);
            $zipManager->setDeliveryAreaByZip($code);
            $invitationManager->clear();
        }
    }

    /**
     * delete
     *
     * @param  mixed  $request
     * @return void
     */
    public function delete(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';

        if ($request->has('cartItemId')) {
            $cartItemId = $request->get('cartItemId');
            $cartItem = CartItem::find($cartItemId);

            if (
                !session()->has('invitation') &&
                $cartItem &&
                $cartItem->cart->user &&
                !$cartItem->cart->user->active_cart_id
            ) {
                $data['msg'] = 'Your cart is empty';
            } elseif (
                !session()->has('invitation') &&
                $cartItem &&
                $cartItem->cart->order &&
                in_array($cartItem->cart->order->status, ['Delivered', 'Canceled'])
            ) {
                if ($cartItem->cart->user) {
                    $user = $cartItem->cart->user;
                    $user->fresh();
                    $user->active_cart_id = null;
                    $user->save();
                }

                $cartItem->cart->status = 0;
                $cartItem->cart->save();
                $data['msg'] = 'The order has been placed, hence you can not update the item quantity';
            } elseif ($cartItem) {
                // Delete cart item
                $result = $cartItem->deleteItem();
                $data['status'] = true;
                $data['msg'] = 'Success';
                $data['result'] = $result;

                // START - Check if remaining items are eligible for discount, if not remove the coupon
                $discountFreeCategoryMessage = '';
                $cartInfo = app(CartManager::class)->getActiveCart();

                if ($cartInfo->promotion_type_id) {
                    $remainingItems = $cartInfo->items()->get();
                    $hasDiscountEligibleItem = false;

                    foreach ($remainingItems as $item) {
                        if (!app(CouponManager::class)->isDiscountFreeCartItem($item)) {
                            $hasDiscountEligibleItem = true;
                            break;
                        }
                    }

                    if (!$hasDiscountEligibleItem) {
                        // Remove the coupon as no items are eligible for discount
                        $cartInfo->deleteCartDiscount($cartInfo->coupon);
                        $discountFreeCategoryMessage .=
                            'The coupon has been removed since no items in your cart qualify for discounts.';
                    }
                }
                // END - Check if remaining items are eligible for discount, if not remove the coupon

                // START - Check if cart item has serving ware option and delete it if none of rest cart items have added serving ware option
                $cart_id = $cartInfo->id;
                $uniqueAvailableServingOptions = getCartItemCategoryServingTags();

                $assignedServingOption = Offmenu::where('cart_id', $cart_id)->first();

                $deletingSevingOption = false;

                if (
                    $assignedServingOption &&
                    !in_array($assignedServingOption->serving_option_id, $uniqueAvailableServingOptions->toArray())
                ) {
                    // Delete the assigned serving ware option
                    $assignedServingOption->delete();
                    $deletingSevingOption = true;

                    // Set paper products to 0
                    if ($cartInfo && $cartInfo->shipping) {
                        $cartInfo->shipping->paper_products = 0;
                        $cartInfo->save();
                    }
                }

                // Update cart total
                $cartInfo->calculateAndUpdate();
                // END - Check if cart item has serving ware option and delete it if none of rest cart items have added serving ware option

                $message = 'Removed ' . $cartItem->product_name . ' from bag. ';

                if ($deletingSevingOption) {
                    $message .= ' Also, Serving ware option has been removed.';
                }

                $count = app(CartManager::class)->getCartCount();

                if ($count == 0 && $cartInfo->order_id) {
                    // Cancel the order if cart is empty
                    $order = Order::find($cartInfo->order_id);
                    $order->status = 'Canceled';
                    $order->save();

                    $message = 'Order has been canceled as cart is empty. Please start a new order.';
                }

                // Append $discountFreeCategoryMessage in $message
                if (!empty($discountFreeCategoryMessage)) {
                    $message .= ' ' . $discountFreeCategoryMessage;
                }

                Session::flash('notify-success', $message);
            } else {
                $data['msg'] = 'This item is not exist in the cart';
            }
        }

        return response()->json($data);
    }

    /**
     * updateMsg
     *
     * @param  mixed  $request
     * @return void
     */
    public function updateMsg(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request->has('cartId') && $request->has('msg')) {
            $cartInfo = Cart::find($request->get('cartId'));
            if ($cartInfo && $cartInfo->order && in_array($cartInfo->order->status, ['Delivered', 'Canceled'])) {
                if ($cartInfo->user) {
                    $user = $cartInfo->user;
                    $user->fresh();
                    $user->active_cart_id = null;
                    $user->save();
                }
                $cartInfo->status = 0;
                $cartInfo->save();
                $data['msg'] = 'The order has been placed, hence you can not update the personalised message';
            } elseif ($cartInfo) {
                $result = Cart::where(['id' => $request->get('cartId')])->update([
                    'personalized_message' => $request->get('msg'),
                ]);

                if (!empty($result)) {
                    $data['status'] = true;
                    $data['msg'] = 'Success';
                    $data['result'] = $result;
                }
            } else {
                $data['msg'] = 'Your cart is empty';
            }
        }

        return response()->json($data);
    }

    /**
     * storeDelivery
     *
     * @return void
     */
    public function storeDelivery()
    {
        $data['status'] = false;
        $data['message'] = 'Something went wrong, please try again';
        $data['result'] = [];
        $cartInfo = app(CartManager::class)->getActiveCart();
        $itemAvailableValidation = app(CartManager::class)->storeItemValidation($cartInfo);

        $deliveryTimeSelected = request()->all()['deliveryOption']['deliveryTimeSelected']; // Format: 12/02/2023
        // Change the date format to Y-m-d
        $deliveryDate = date('Y-m-d', strtotime(request()->all()['deliveryOption']['deliveryDateValue'])); // 2023-12-02
        // Check if the delivery date is on weekends
        $isWeekend = date('N', strtotime($deliveryDate)) >= 6; // true

        // Check if weekend delivery is enabled for given zipcode
        if ($cartInfo->cafe->market->allow_weekend_orders == 0 && $isWeekend) {
            $data['message'] =
                'Currently, we are not allowing weekend orders for the specified zip code. Please choose a different delivery date that is not on the weekend.';

            return response()->json($data);
        }

        // 68, 26, 69, 27, 70, 28, 71, 29, 72, 30, 73, 31, 74, 32 is night order time slots
        $nightOrderTimeSlots = [68, 26, 69, 27, 70, 28, 71, 29, 72, 30, 73, 31, 74, 32];
        $deliveryTimeSelected = request()->all()['deliveryOption']['deliveryTimeSelected'];

        // Check if night delivery is enabled for given zipcode
        if ($cartInfo->cafe->market->allow_night_orders == 0 && in_array($deliveryTimeSelected, $nightOrderTimeSlots)) {
            $data['message'] =
                'Currently, we are not available to delivery after 4:30 PM for the specified zip code. Please select a delivery time before 4:30 PM.';

            return response()->json($data);
        }

        if ($itemAvailableValidation['status'] == false) {
            $data['message'] = $itemAvailableValidation['msg'];
        } else {
            // Old dates to turned off '12/24/2020', '12/25/2020', '12/31/2020','01/01/2021',
            $temporaryDisableDates = ['11/25/2021', '11/26/2021', '12/24/2021', '12/31/2021', '01/01/2022'];
            $inputData = request()->all();
            if ($inputData['isDeliverySelected']) {
                $deliveryDate = $inputData['deliveryOption']['deliveryDateValue'];
            } else {
                $deliveryDate = $inputData['pickupOption']['pickupDateValue'];
            }
            if (in_array($deliveryDate, $temporaryDisableDates)) {
                $data['message'] = 'Sorry, We are not delivering the choosen date';
            } else {
                $storeDelivery = new StoreDelivery(request()->all());
                if ($cartInfo && $cartInfo->order && in_array($cartInfo->order->status, ['Delivered', 'Canceled'])) {
                    if ($cartInfo->user) {
                        $user = $cartInfo->user;
                        $user->fresh();
                        $user->active_cart_id = null;
                        $user->save();
                    }
                    $cartInfo->status = 0;
                    $cartInfo->save();
                    $data['message'] = 'The order has been placed, hence you can not update the delivery information';
                } elseif ($cartInfo) {
                    $guestUserInfoValidation = $storeDelivery->guestValidate();
                    $zipcodeValidation = $storeDelivery->deliveryAreaValidation();
                    if ($guestUserInfoValidation && $zipcodeValidation) {
                        $result = $storeDelivery->store();
                        $data['status'] = true;
                        $data['message'] = 'Success';
                        $data['result'] = $result;
                        $cartInfo = app(CartManager::class)->getActiveCart();
                        $cartInfo = $cartInfo->fresh();
                        $this->storeTipAmount($cartInfo);
                    } else {
                        $data['message'] = 'Please add required fields in mentioned format';
                    }
                } else {
                    $data['message'] = 'Your cart is empty';
                }
            }
        }

        $this->updateUser(request()->all());

        return response()->json($data);
    }

    /**
     * Update the user with the given data.
     *
     * @param  array  $data  The data to update the user.
     * @return void
     */
    public function updateUser($data)
    {
        if (Auth::user() && Auth::user()->social_login == 1) {
            $zipManager = app(ZipManager::class);

            $zipcode = $data['deliveryOption']['zipcode'];

            $findClosestCafe = $zipManager->findClosestZipcodeHavingCafe($zipcode);
            $cafeIdExist = null;

            if ($findClosestCafe && $findClosestCafe->count() > 0) {
                $cafeIdExist = $findClosestCafe[0]->cafe->id;
            } elseif (session()->has('UserDeliveryInformation.alontiDeliveryArea')) {
                $cafeIdExist = $findClosestCafe->cafe->id;
            }

            $user = User::find(Auth::user()->id);
            $user->phone = isset($data['userDetails']['phone']) ? $data['userDetails']['phone'] : null;
            $user->secondary_phone = isset($data['userDetails']['secondaryPhone'])
                ? $data['userDetails']['secondaryPhone']
                : null;
            $user->company = isset($data['orderDetails']['company']) ? $data['orderDetails']['company'] : null;
            $user->physical_addr = isset($data['deliveryOption']['address'])
                ? $data['deliveryOption']['address']
                : null;
            $user->physical_addr2 = isset($data['deliveryOption']['address_two'])
                ? $data['deliveryOption']['address_two']
                : null;
            $user->physical_state = isset($data['deliveryOption']['stateSelected'])
                ? $data['deliveryOption']['stateSelected']
                : null;
            $user->physical_city = isset($data['deliveryOption']['city']) ? $data['deliveryOption']['city'] : null;
            $user->physical_zip = isset($data['deliveryOption']['zipcode']) ? $data['deliveryOption']['zipcode'] : null;
            $user->addr = isset($data['deliveryOption']['address']) ? $data['deliveryOption']['address'] : null;
            $user->city = isset($data['deliveryOption']['city']) ? $data['deliveryOption']['city'] : null;
            $user->state = isset($data['deliveryOption']['stateSelected'])
                ? $data['deliveryOption']['stateSelected']
                : null;
            $user->addr2 = isset($data['deliveryOption']['address_two'])
                ? $data['deliveryOption']['address_two']
                : null;
            $user->zip = isset($data['deliveryOption']['zipcode']) ? $data['deliveryOption']['zipcode'] : null;
            $user->cafe_id = $cafeIdExist;
            $user->group_id = 5;
            $user->customermenu_id = 1;
            $user->type = 0;
            $user->payment_id = 4;
            $user->social_login = 0; // Set social_login to 0 as user is updating his/her details
            $user->save();
        }
    }

    /**
     * Store serving ware option and update cart total
     *
     * @param  mixed  $request
     * @return void
     */
    public function storeServingOption(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['message'] = 'Something went wrong, Please try again!';
        $validated = true;

        // Form validation
        $required_fields = [
            'selectedOption' => 'required_without:paper_products',
            'headCount' => 'required_without:paper_products',
            'paper_products' => 'required_without_all:headCount,selectedOption',
        ];

        try {
            $request->validate($required_fields);
        } catch (ValidationException $e) {
            // Handle the errors
            // Get the first error message
            $error = $e->errors();
            $error = array_values($error)[0][0];
            $data['message'] = $error;
            $validated = false;
        }

        if (!$validated) {
            return response()->json($data);
        }

        // Run below code block only if validation is passed
        $cartInfo = app(CartManager::class)->getActiveCart();

        if (!is_null($request->selectedOption) && !is_null($request->headCount)) {
            $option_id = $request->get('selectedOption');

            // Fetch serving ware option where id = $request->get('selectedOption')
            $serving_option = ServingOption::find($option_id);

            Offmenu::updateOrCreate(
                [
                    'cart_id' => $cartInfo->id,
                ],
                [
                    'serving_option_id' => $serving_option->id,
                    'price' => $serving_option->price,
                    'qty' => $request->get('headCount'),
                    'txbl' => 1,
                    'flag' => 6,
                ]
            );

            // Update cart with total = total
            $cartInfo->calculateAndUpdate();
        }

        // Update Shipping data
        if ($cartInfo->shipping) {
            $shipping = [
                'paper_products' => $request->get('paper_products') ? 1 : 0,
            ];

            $cartInfo->shipping->update($shipping);
        }

        $data['status'] = true;
        $data['message'] = 'Success';

        return response()->json($data);
    }

    /**
     * Store serving ware option and update cart total
     *
     * @param  mixed  $request
     * @return void
     */
    public function deleteServingOption(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['message'] = 'Something went wrong, Please try again!';

        // Run below code block only if validation is passed
        $cartInfo = app(CartManager::class)->getActiveCart();
        $offmenu = Offmenu::where('cart_id', $cartInfo->id)->delete();

        // Check if serving ware option is deleted
        if ($offmenu) {
            $data['status'] = true;
            $data['message'] = 'Success';
        }

        return response()->json($data);
    }

    /**
     * storePayment
     *
     * @return void
     */
    public function storePayment()
    {
        $data['status'] = true;
        $data['message'] = 'Success';
        $cartInfo = app(CartManager::class)->getActiveCart();
        $itemAvailableValidation = app(CartManager::class)->storeItemValidation($cartInfo);
        if ($itemAvailableValidation['status'] == false) {
            $data['message'] = $itemAvailableValidation['msg'];
        } else {
            if ($cartInfo && $cartInfo->order && in_array($cartInfo->order->status, ['Delivered', 'Canceled'])) {
                if ($cartInfo->user) {
                    $user = $cartInfo->user;
                    $user->fresh();
                    $user->active_cart_id = null;
                    $user->save();
                }
                $cartInfo->status = 0;
                $cartInfo->save();
                $data['message'] = 'The order has been placed, hence you can not update the payment information';
            } elseif ($cartInfo) {
                $storePayment = new StorePayment(request()->all());
                $validation = $storePayment->paymentTypeValidation();
                if (!$validation['status']) {
                    $data['status'] = false;
                    $data['message'] = $validation['msg'];
                } else {
                    $result = $storePayment->store();
                    if (request('isCardSelected')) {
                        try {
                            $result = $storePayment->processPayment();
                        } catch (\GuzzleHttp\Exception\ClientException $e) {
                            $errRes = $e->getResponse()->getBody(true)->getContents();
                            $errorMsg =
                                'Status ' .
                                $e->getResponse()->getStatusCode() .
                                ' ' .
                                $e->getResponse()->getBody(true)->getContents();
                            $storePayment->storeApiErrorLog($errorMsg);
                            $data['status'] = false;
                            $data['message'] = $this->clientErrorHandler($errRes);
                        }
                    }
                }
            }
        }

        return response()->json($data);
    }

    /**
     * clientErrorHandler
     *
     * @param  mixed  $e
     * @return void
     */
    public function clientErrorHandler($e)
    {
        $exceptionData = json_decode($e);
        $error = '';
        $errorData = [];
        if (isset($exceptionData->error)) {
            $errorData[] = $exceptionData->error;
        }
        if (isset($exceptionData->error_description)) {
            $errorData[] = $exceptionData->error_description;
        }
        if (isset($exceptionData->errors)) {
            $error = json_encode($exceptionData->errors);
            $errorData = json_decode($error);
            if (!empty($errorData) && !is_array($errorData)) {
                $msg = [];
                foreach ($errorData as $key => $value) {
                    $msg[] = implode(',', $value);
                }
                $errorMsg = implode(',', $msg);
            }
        } else {
            $errorMsg = '';
            if (!empty($errorData)) {
                $errorMsg = implode(', ', $errorData);
            }
        }

        return ucfirst($errorMsg);
    }

    /**
     * anetValidationMsg
     *
     * @param  mixed  $errorMsg
     * @return void
     */
    public function anetValidationMsg($errorMsg)
    {
        if (stripos($errorMsg, 'The actual length is greater than the MaxLength value')) {
            return 'The card number actual length is greater than the MaxLength value';
        } else {
            return $errorMsg;
        }
    }

    /**
     * placeOrder
     *
     * @param  mixed  $request
     * @return void
     */
    public function placeOrder(Request $request)
    {
        // Log::channel('ghostinvoice')->info('-----------------------------------START-------------------------------------');
        // Log::channel('ghostinvoice')->info('NEW PLACE ORDER ATTEMPT: ');

        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';

        if ($request->has('cartId')) {
            $cartInfo = app(CartManager::class)->getActiveCart();

            if (empty($cartInfo)) {
                $data['msg'] = 'Your request is currently being processed. Please check the Order History for updates.';
                // Log::channel('ghostinvoice')->info('Possible ghost invoice attempt when cart is empty.');
            } elseif (Session::has('cart-id-lock')) {
                // Order has been processed already
                $data['msg'] = 'Your request is currently being processed. Please check the Order History for updates.';
                // Log::channel('ghostinvoice')->info('Possible ghost invoice attempt when cart-id-lock already exist in session.');
            } else {
                $itemAvailableValidation = app(CartManager::class)->storeItemValidation($cartInfo);
                // Log::channel('ghostinvoice')->info('$itemAvailableValidation: ' . json_encode($itemAvailableValidation));

                if ($itemAvailableValidation['status'] == false) {
                    $data['msg'] = $itemAvailableValidation['msg'];
                } else {
                    // Log::channel('ghostinvoice')->info('$cartInfo: ' . json_encode($cartInfo));
                    // Log::channel('ghostinvoice')->info('$cartInfo->items: ' . json_encode($cartInfo->items));
                    $orderPlacement = new OrderPlacement(request()->all());
                    //  Log::channel('ghostinvoice')->info('Ready to place an order...');
                    $result = $orderPlacement->placeOrder();
                    //  Log::channel('ghostinvoice')->info('The order has been placed. Below is the result:');
                    // Log::channel('ghostinvoice')->info($result);

                    if ($result) {
                        $data['status'] = true;
                        $data['msg'] = 'Success';
                        $data['result'] = $result;

                        // Forget cart-id-lock from session
                        Session::forget('cart-id-lock');
                    }
                }
            }
        }

        // Log the response in custom log file
        // Log::channel('ghostinvoice')->info('Place order response: ' . json_encode($data));
        //  Log::channel('ghostinvoice')->info('-----------------------------------END-------------------------------------');
        return response()->json($data);
    }

    /**
     * applyCoupon
     *
     * @param  mixed  $request
     * @return void
     */
    public function applyCoupon(CouponRequest $request)
    {
        $result = [];
        $result['status'] = false;
        $result['message'] = 'Something went wrong, Please try again!';
        $cartInfo = app(CartManager::class)->getActiveCart();
        if ($cartInfo && $cartInfo->order && in_array($cartInfo->order->status, ['Delivered', 'Canceled'])) {
            if ($cartInfo->user) {
                $user = $cartInfo->user;
                $user->fresh();
                $user->active_cart_id = null;
                $user->save();
            }
            $cartInfo->status = 0;
            $cartInfo->save();
            $result['message'] = 'The order has been placed, hence you can not apply the coupon';
        } elseif ($cartInfo) {
            $result = app(CouponManager::class)->applyCoupon($request->input('coupon'));
        } else {
            $result['message'] = 'Your cart is empty';
        }

        return response()->json($result);
    }

    /**
     * resetCoupon
     *
     * @param  mixed  $request
     * @return void
     */
    public function resetCoupon(Request $request)
    {
        $result = [];
        $result['status'] = false;
        $result['msg'] = 'Something went wrong, Please try again!';
        $cartInfo = app(CartManager::class)->getActiveCart();
        if ($cartInfo && $cartInfo->order && in_array($cartInfo->order->status, ['Delivered', 'Canceled'])) {
            if ($cartInfo->user) {
                $user = $cartInfo->user;
                $user->fresh();
                $user->active_cart_id = null;
                $user->save();
            }
            $cartInfo->status = 0;
            $cartInfo->save();
            $result['message'] = 'The order has been placed, hence you can not reset the coupon';
        } elseif ($cartInfo) {
            $result = app(ResetCoupon::class)->resetPromocode($request->input('coupon'));
        } else {
            $result['message'] = 'Your cart is empty';
        }

        return response()->json($result);
    }

    /**
     * Apply amazon rewards at checkout
     *
     * @param  mixed  $request
     * @return void
     */
    public function applyRewards(Request $request)
    {
        // dd($request->all());
        $result = [];
        $result['status'] = false;
        $result['message'] = 'Something went wrong, Please try again!';
        $cartInfo = app(CartManager::class)->getActiveCart();
        if ($cartInfo && $cartInfo->order && in_array($cartInfo->order->status, ['Delivered', 'Canceled'])) {
            if ($cartInfo->user) {
                $user = $cartInfo->user;
                $user->fresh();
                $user->active_cart_id = null;
                $user->save();
            }
            $cartInfo->status = 0;
            $cartInfo->save();
            $result['message'] = 'The order has been placed, hence you can not apply the rewards';
        } elseif ($cartInfo) {
            // store the amazon reward amount in the cart and update the total amount
            $cartInfo->amazon_reward_applied = $request->input('rewardAmount');
            $cartInfo->total = $cartInfo->total - $request->input('rewardAmount');
            $cartInfo->save();
            $result['status'] = true;
            $result['message'] = 'Amazon rewards applied successfully';
            $result['rewardAmount'] = $request->input('rewardAmount');
            $result['totalAmount'] = $cartInfo->total;
        } else {
            $result['message'] = 'Your cart is empty';
        }

        return response()->json($result);
    }

    /**
     * Reset amazon rewards at checkout
     *
     * @param  mixed  $request
     * @return void
     */
    public function resetRewards(Request $request)
    {
        $result = [];
        $result['status'] = false;
        $result['msg'] = 'Something went wrong, Please try again!';
        $cartInfo = app(CartManager::class)->getActiveCart();
        if ($cartInfo && $cartInfo->order && in_array($cartInfo->order->status, ['Delivered', 'Canceled'])) {
            if ($cartInfo->user) {
                $user = $cartInfo->user;
                $user->fresh();
                $user->active_cart_id = null;
                $user->save();
            }
            $cartInfo->status = 0;
            $cartInfo->save();
            $result['message'] = 'The order has been placed, hence you can not reset the rewards';
        } elseif ($cartInfo) {
            // remove the amazon reward amount from the cart and update the total amount
            $cartInfo->amazon_reward_applied = 0;
            $cartInfo->total = $cartInfo->total + $request->input('rewardAmount');
            $cartInfo->save();
            $result['status'] = true;
            $result['message'] = 'Amazon rewards reset successfully';
            $result['rewardAmount'] = $request->input('rewardAmount');
            $result['totalAmount'] = $cartInfo->total;
        } else {
            $result['message'] = 'Your cart is empty';
        }

        return response()->json($result);
    }

    /**
     * updateTip
     *
     * @param  mixed  $request
     * @return void
     */
    public function updateTip(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request->has('cartId') && ($request->has('tipPercentage') || $request->has('customTip'))) {
            $cartInfo = Cart::find($request->get('cartId'));
            if ($cartInfo && $cartInfo->order && in_array($cartInfo->order->status, ['Delivered', 'Canceled'])) {
                if ($cartInfo->user) {
                    $user = $cartInfo->user;
                    $user->fresh();
                    $user->active_cart_id = null;
                    $user->save();
                }
                $cartInfo->status = 0;
                $cartInfo->save();
                $data['msg'] = 'The order has been placed, hence you can not update the tip amount';
            } elseif ($cartInfo) {
                $flag = $request->get('customTip') != null ? 'customTip' : 'percentage';
                $info = $this->storeTipAmount(
                    $cartInfo,
                    $request->get('tipPercentage'),
                    $request->get('customTip'),
                    $flag
                );
                $data['status'] = true;
                $data['msg'] = 'Success';
                $data['result'] = $info;
            } else {
                $data['msg'] = 'Your cart is empty';
            }
        }

        return response()->json($data);
    }

    /**
     * storeTipAmount
     *
     * @param  mixed  $cartInfo
     * @param  mixed  $percentage
     * @param  mixed  $customTip
     * @param  mixed  $tipFlag
     * @return void
     */
    public function storeTipAmount($cartInfo, $percentage = null, $customTip = null, $tipFlag = '')
    {
        if ($tipFlag == 'percentage') {
            $percentage = (float) $percentage;
            if ($percentage == '0') {
                $cartInfo->gratuity_percentage = 0;
                $cartInfo->gratuity = null;
            } else {
                $cartInfo->gratuity_percentage = $percentage > 0 ? $percentage : 0;
                $cartInfo->gratuity =
                    $percentage > 0
                        ? round(
                            ($cartInfo->taxable +
                                $cartInfo->nontaxable +
                                $cartInfo->delivery_fee +
                                $cartInfo->sales_tax) *
                                ($percentage / 100),
                            2
                        )
                        : null;
            }
        } elseif ($tipFlag == 'customTip') {
            $customTip = (float) $customTip;
            $cartInfo->gratuity_percentage = null;
            $cartInfo->gratuity = $customTip;
        } else {
            if (is_null($cartInfo->gratuity_percentage) && is_null($cartInfo->gratuity)) {
                $cartInfo->gratuity_percentage = config('custom.payment.tip.default');
                $cartInfo->gratuity = round(
                    ($cartInfo->taxable + $cartInfo->nontaxable + $cartInfo->delivery_fee + $cartInfo->sales_tax) *
                        (config('custom.payment.tip.default') / 100),
                    2
                );
            } elseif (!is_null($cartInfo->gratuity_percentage) && is_null($cartInfo->gratuity)) {
                if ($cartInfo->gratuity_percentage > 0) {
                    $cartInfo->gratuity_percentage = $cartInfo->gratuity_percentage;
                    $cartInfo->gratuity = round(
                        ($cartInfo->taxable + $cartInfo->nontaxable + $cartInfo->delivery_fee + $cartInfo->sales_tax) *
                            ($cartInfo->gratuity_percentage / 100),
                        2
                    );
                } else {
                    $cartInfo->gratuity_percentage = $cartInfo->gratuity_percentage;
                    $cartInfo->gratuity = $cartInfo->gratuity;
                }
            } elseif (is_null($cartInfo->gratuity_percentage) && !is_null($cartInfo->gratuity)) {
                $cartInfo->gratuity_percentage = $cartInfo->gratuity_percentage;
                $cartInfo->gratuity = $cartInfo->gratuity;
            } elseif (!is_null($cartInfo->gratuity_percentage) && !is_null($cartInfo->gratuity)) {
                $cartInfo->gratuity_percentage = $cartInfo->gratuity_percentage;
                $cartInfo->gratuity = round(
                    ($cartInfo->taxable + $cartInfo->nontaxable + $cartInfo->delivery_fee + $cartInfo->sales_tax) *
                        ($cartInfo->gratuity_percentage / 100),
                    2
                );
            }
        }
        // reduce amazon reward applied at checkout
        $cartInfo->total =
            $cartInfo->taxable +
            $cartInfo->nontaxable +
            $cartInfo->delivery_fee +
            $cartInfo->sales_tax +
            $cartInfo->gratuity -
            $cartInfo->amazon_reward_applied;
        $cartInfo->save();

        return $cartInfo;
    }

    /**
     * addFreeItem
     *
     * @param  mixed  $request
     * @return void
     */
    public function addFreeItem(Request $request)
    {
        $result = app(CouponManager::class)->addFreeItem($request->all());

        return response()->json($result);
    }

    /**
     * updateOrder
     *
     * @param  mixed  $request
     * @return void
     */
    public function updateOrder(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request->has('cartId')) {
            $cartInfo = app(CartManager::class)->getActiveCart();
            $itemAvailableValidation = app(CartManager::class)->storeItemValidation($cartInfo);
            if ($itemAvailableValidation['status'] == false) {
                $data['msg'] = $itemAvailableValidation['msg'];
            } else {
                // $couponErrorValidation  = false;
                // if($cartInfo->coupon_id){
                //     $productIds = [];
                //     foreach($cartInfo->items as $item) {
                //         $productIds[] = $item->product_id;
                //     }
                //     $coupon = Coupon::getDetails($cartInfo->coupon->coupon, $cartInfo, $productIds);
                //     if(!$coupon){
                //         $couponErrorValidation = true;
                //     }
                // }
                // if($couponErrorValidation){
                //     $data['msg'] = 'Invalid Coupon! Please enter a valid coupon';
                // } else {
                if ($cartInfo && $cartInfo->order && in_array($cartInfo->order->status, ['Delivered', 'Canceled'])) {
                    if ($cartInfo->user) {
                        $user = $cartInfo->user;
                        $user->fresh();
                        $user->active_cart_id = null;
                        $user->save();
                    }
                    $cartInfo->status = 0;
                    $cartInfo->save();
                    $data['msg'] = 'The order has been placed, hence you can not update the order';
                } elseif ($cartInfo) {
                    $orderPlacement = new OrderPlacement(request()->all());
                    try {
                        $output = $orderPlacement->updateOrder();
                        if ($output['status']) {
                            $data['status'] = true;
                            $data['msg'] = 'Success';
                            $data['result'] = $output['result'];
                        } else {
                            $data['msg'] = $output['message'];
                        }
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                        $errRes = $e->getResponse()->getBody(true)->getContents();
                        $errorMsg =
                            'Status ' .
                            $e->getResponse()->getStatusCode() .
                            ' ' .
                            $e->getResponse()->getBody(true)->getContents();
                        $storePayment->storeApiErrorLog($errorMsg);
                        $data['status'] = false;
                        $data['msg'] = $this->clientErrorHandler($errRes);
                    }
                }
            }
        }

        return response()->json($data);
    }

    /**
     * splCookieValidation
     *
     * @param  mixed  $request
     * @return void
     */
    public function splCookieValidation(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request->has('cartId')) {
            $id = $request->get('cartId');
            $cart = Cart::find($id);
            $wcCategoryIds = Category::where('delivery_exception', 1)->warmCookieCategory()->pluck('id');
            $wcDozen = 0;
            $wcExist = false;
            $cart->items->map(function ($item) use (&$wcDozen, &$wcExist, $wcCategoryIds) {
                if ($wcCategoryIds->contains($item->category_id)) {
                    $wcDozen += $item->quantity;
                    $wcExist = true;
                }
            });
            $data['status'] = true;
            $data['msg'] = 'Success';
            $data['result'] = ['dozen' => $wcDozen, 'wcExist' => $wcExist];
        }

        return response()->json($data);
    }

    /**
     * removeCard
     *
     * @param  mixed  $request
     * @return void
     */
    public function removeCard(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request->has('removeCardId')) {
            $id = $request->get('removeCardId');
            $paymentProfile = CimPaymentProfile::find($id);
            $cimPaids = CimPaid::where([
                'profile_id' => $paymentProfile->profile_id,
                'payment_profile_id' => $paymentProfile->payment_profile_id,
            ])->get();
            $deleteProfile = true;
            if ($cimPaids) {
                foreach ($cimPaids as $key => $value) {
                    if ($value->status == 'Authorized') {
                        $deleteProfile = false;
                        break;
                    }
                }
            }
            if (!$deleteProfile) {
                $data['msg'] =
                    'Your card could not be deleted, since authorization has been alredy done with this card. Please void previously authorized transaction to delete this card.';
            } else {
                // $data['status'] = true;
                $cartInfo = app(CartManager::class)->getActiveCart();
                $authorizeDetail['customer_profile_id'] = $paymentProfile->profile_id;
                $authorizeDetail['customer_payment_profile_id'] = $paymentProfile->payment_profile_id;
                $service = new PaymentService($cartInfo->user, $cartInfo);
                try {
                    $response = $service->deletePaymentProfile($authorizeDetail);
                    if ($cartInfo->cim_payment_profile_id == $paymentProfile->id) {
                        $cartInfo->cim_payment_profile_id = null;
                        $cartInfo->save();
                    }
                    $paymentProfile->delete();
                    $data['status'] = true;
                    $data['msg'] = 'Your card is deleted.';
                } catch (AuthorizenetException $e) {
                    $errorMsg = $e->getMessage();
                    $data['status'] = false;
                    $data['msg'] = $errorMsg;
                }
            }
        }

        return response()->json($data);
    }
}
