<?php

declare(strict_types=1);


namespace App\Alonti\Order;

use App\Alonti\Payment\Drivers\AuthorizenetException;
use App\Alonti\PaymentService\PaymentService;
use App\Alonti\PaymentService\PaytraceService;
use App\Models\Cart;
use App\Models\Cim;
use App\Models\Offmenu;
use App\Models\Order;
use App\Models\Reward;
use App\Models\Shipping;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class OrderPlacement
{
    public $data;

    public $cart;

    public $state;

    public function __construct($data)
    {
        $this->data = collect($data);
        $this->cart = Cart::find($this->data->get('cartId'));
        $this->state = State::pluck('name', 'id')->toArray();
    }

    public function placeOrder()
    {
        Log::info('placeOrder called', [
            'cart_id' => $this->cart ? $this->cart->id : null,
            'cart_items' => isset($this->cart->items) ? count($this->cart->items) : null,
            'cart_order_id' => $this->cart ? $this->cart->order_id : null,
            'session_cart_id_lock' => Session::get('cart-id-lock'),
        ]);

        // If session has cart-id-lock then this is an attempt to generate a GHOST ORDER/INVOICE so return false
        if (Session::has('cart-id-lock') && Session::get('cart-id-lock') == $this->cart->id) {
            Log::warning('Cart ID lock detected, aborting order placement', [
                'cart_id' => $this->cart->id,
            ]);

            return false;
        }

        if (
            isset($this->cart) &&
            isset($this->cart->items) &&
            !empty($this->cart->items) &&
            empty($this->cart->order_id)
        ) {
            Log::info('Proceeding with order placement', [
                'cart_id' => $this->cart->id,
            ]);
            // Put cart->id in session
            Session::put('cart-id-lock', $this->cart->id);

            $data = $this->prepareOrderData();
            Log::debug('Order data prepared', $data);

            $order = Order::create($data);
            Log::info('Order created', [
                'order_id' => $order ? $order->id : null,
            ]);

            if ($data['gift_card_rewards']) {
                Log::info('Updating order in reward', [
                    'cart_id' => $this->cart->id,
                    'order_id' => $order->id,
                    'cafe_id' => $order->cafe_id,
                ]);
                $this->updateOrderInReward($this->cart->id, $order->id, $order->cafe_id);
            }

            $this->cart->session_id = null;
            $this->cart->order_id = $order->id;
            $this->cart->save();
            Log::info('Cart updated with order_id', [
                'cart_id' => $this->cart->id,
                'order_id' => $order->id,
            ]);

            if ($this->cart->coupon) {
                Log::info('Updating offmenu for coupon', [
                    'cart_id' => $this->cart->id,
                    'order_id' => $order->id,
                ]);
                Offmenu::updateOffmenu($this->cart, $order->id);
            }

            if (Auth::user()) {
                $user = $this->cart->user->find(Auth::user()->id);
                if ($user) {
                    $user->active_cart_id = null;
                    $user->save();
                    Log::info('User active_cart_id set to null', [
                        'user_id' => $user->id,
                    ]);
                }
            }

            if ($this->cart->cim_payment_profile_id) {
                $authRecord = $this->generatePayableRecord($order);
                Log::debug('Generated payable record', $authRecord);
                if ($order->gratuity == 0) {
                    $authRecord['auth_amount'] = $order->auth_amount;
                }
                $this->cart->paymentProfile->paids()->create($authRecord);
                Log::info('Payable record created for payment profile', [
                    'payment_profile_id' => $this->cart->paymentProfile->id,
                ]);
            }

            if ($order) {
                if ($this->cart->abandonedCart) {
                    $this->cart->abandonedCart->deleted_at = Carbon::now();
                    $this->cart->abandonedCart->update();
                    Log::info('Abandoned cart marked as deleted', [
                        'cart_id' => $this->cart->id,
                    ]);
                }
            }

            if ($this->cart->groupOrderConfig) {
                $this->cart->groupOrderConfig->order_id = $order->id;
                $this->cart->groupOrderConfig->save();
                Log::info('Group order config updated', [
                    'group_order_config_id' => $this->cart->groupOrderConfig->id,
                    'order_id' => $order->id,
                ]);
            }

            Log::info('Order placement completed', [
                'order_id' => $order->id,
            ]);

            return $order;
        }

        Log::warning('Order placement failed: cart missing, empty, or already has order_id', [
            'cart_id' => $this->cart ? $this->cart->id : null,
        ]);

        return false;
    }

    public function flushDeliveryAreaFromSession()
    {
        session()->forget('UserDeliveryInformation');
    }

    public function updateOrder()
    {
        $order = Order::find($this->cart->order_id);
        $data = $this->prepareOrderData();
        unset($data['ordate']);
        $paidRecord = $this->cart->order->paid;
        $paymentProfileName = $this->cart->paymentProfile ? $this->cart->paymentProfile->gateway_name : '';
        if (
            strtolower($paymentProfileName) == 'paytrace' &&
            $paidRecord &&
            $paidRecord->transaction_id &&
            $paidRecord->isAuthorizedOrPaid()
        ) {
            $this->voidPaytracePayment($paidRecord, $order);
        }

        $result = $order->update($data);

        // Update sales_area_id
        $order->sales_area_id = $order->salesAreaBasedOnZipCode();
        $order->save();

        if ($data['gift_card_rewards']) {
            $this->updateOrderInReward($this->cart->id, $order->id, $order->cafe_id);
        }
        if ($result) {
            if ($this->cart->coupon) {
                Offmenu::updateOffmenu($this->cart, $order->id);
            } else {
                Offmenu::deleteOffmenu($order->id);
            }
            $updatedOrder = Order::find($this->cart->order_id);
            $paidRecord = $this->cart->order->paid;
            if ($this->cart->cim_payment_profile_id) {
                $authRecord = $this->generatePayableRecord($updatedOrder);
                if ($updatedOrder->gratuity == 0) {
                    $authRecord['auth_amount'] = $updatedOrder->auth_amount;
                }
                if ($paidRecord) {
                    $this->cart->order->paid->update($authRecord);
                } else {
                    $this->cart->paymentProfile->paids()->create($authRecord);
                }
            }
            $order->user->active_cart_id = null;
            $order->user->save();
            $this->cart->status = 0;
            $this->cart->save();
            $output['status'] = true;
            $output['result'] = $updatedOrder;

            return $output;
        } else {
            $output['status'] = false;
            $output['message'] = 'Order is not updated';

            return $output;
        }
    }

    public function frequentUpdateOrder()
    {
        if ($this->cart->order_id) {
            $order = Order::find($this->cart->order_id);
            $data = $this->prepareOrderData();
            $result = $order->update($data);

            // Update sales_area_id
            $order->sales_area_id = $order->salesAreaBasedOnZipCode();
            $order->save();

            if ($result) {
                if ($this->cart->coupon) {
                    Offmenu::updateOffmenu($this->cart, $order->id);
                } else {
                    Offmenu::deleteOffmenu($order->id);
                }
            }
        }

        return true;
    }

    public function generatePayableRecord($order)
    {
        $authTotal = round($order->total, 2);
        $cimCustomerProfile = Cim::where('profile_id', $order->cart->paymentProfile->profile_id)->first();
        $authRecord = [
            'cim_payment_profile_id' => $order->cart->paymentProfile->id,
            'user_id' => $order->alonti_user_id,
            'order_id' => $order->id,
            'profile_id' => $order->cart->paymentProfile->profile_id,
            'payment_profile_id' => $order->cart->paymentProfile->payment_profile_id,
            'shipping_id' => $cimCustomerProfile ? $cimCustomerProfile->shipping_id : null,
            'status' => 'Not Paid',
            'paiddate' => date('m/d/Y'),
            'paid_date' => null,
            'transaction_id' => null,
            'approval_code' => null,
            'auth_amount' => $authTotal,
            'total_amount' => null,
            'payment_process' => 'Create',
            'gateway_name' => 'PAYTRACE',
        ];

        return $authRecord;
    }

    public function prepareOrderData()
    {
        $address1 = '';
        $address2 = '';
        $city = '';
        $stateVal = '';
        $zipcode = '';
        $states = State::pluck('name', 'id')->toArray();

        if ($this->cart->shipping->shipping_type == Shipping::TYPE_DELIVERY) {
            $address1 = $this->cart->shipping->address1;
            $address2 = $this->cart->shipping->address2;
            $city = $this->cart->shipping->city;
            $stateVal = is_numeric($this->cart->shipping->state) ? $states[$this->cart->shipping->state] : '';
        }

        $zipcode = $this->cart->shipping->zipcode;
        $orderStatus = 'Ordered';

        $data = [
            'customermenu_id' => Order::CUSTOMER_MENU,
            'payment_id' => $this->cart->payment_id,
            'alonti_user_id' => $this->cart->user_id,
            'group_order_id' => $this->cart->group_order_id,
            'cafe_id' => $this->cart->cafe_id,
            'ordate' => new \Datetime(),
            'd_date' => $this->cart->shipping->delivery_date,
            'time_id' => $this->cart->shipping->delivery_time,
            'd_addr' => isset($address1) ? $address1 : '',
            'second_address' => isset($address2) ? $address2 : '',
            'status' => $orderStatus,
            'taxable' => $this->cart->taxable,
            'nontaxable' => $this->cart->nontaxable,
            'delivery' => $this->cart->delivery_fee,
            'salestax' => $this->cart->sales_tax,
            'total' => $this->cart->total,
            'gratuity' => $this->cart->gratuity,
            'notes' => $this->cart->shipping->delivery_instruction,
            'cookie_special_instruction' => $this->cart->personalized_message,
            'gift_order' => $this->cart->shipping->delivery_as_gift,
            'pdflag' => $this->cart->shipping->shipping_type == Shipping::TYPE_PICKUP ? 0 : 1,
            'web' => Order::WEB_CUSTOMER_SITE,
            'zipcode' => isset($zipcode) ? $zipcode : '',
            'deliveryCity' => isset($city) ? $city : '',
            'state' => is_string($stateVal) ? $stateVal : '',
            'checkout_type' => auth()->user() ? Order::CHECKOUT_TYPE_LOGGED : Order::CHECKOUT_TYPE_GUEST,
            'is_new_order' => Order::IS_NEW_ORDER,
            'porder' => $this->cart->company_payment_access_number,
            'gift_order' => $this->cart->shipping->delivery_as_gift,
            'orderName' => $this->cart->order_name,
            'placetoderby' => $this->cart->user_id,
            'gift_card_rewards' => !$this->cart->promotion_type_id && !$this->cart->coupon_id ? $this->cart->gift_card_rewards : 0,
            'amazon_reward' => $this->cart->amazon_reward_applied,
        ];

        return $data;
    }

    public function updateOrderInReward($cartId, $orderId, $cafeId)
    {
        $rewardExist = app(Reward::class)->cartRewardValue($cartId);

        if ($rewardExist && !$rewardExist->order_id) {
            $rewardExist->order_id = $orderId;
            $rewardExist->cafe_id = $cafeId;
            $rewardExist->save();
        }
    }

    public function prepareCCTrackRecord($order)
    {
        $orderTotal = round($order->total, 2);

        $data = [
            'user_id' => $order->cart->user_id,
            'user_cc_id' => $order->cart->user_cc_id,
            'cart_id' => $order->cart->id,
            'order_id' => $order->id,
            'order_total' => $orderTotal,
            'authorise_amount' => $orderTotal,
            'payment_status' => 'Not Paid',
            'paiddate' => date('Y-m-d H:i:s'),
        ];

        return $data;
    }

    public function voidPaytracePayment($paidRecord, $order)
    {
        $paytrace = new PaytraceService();
        $generateTokenRes = $paytrace->connetPaytrace();
        $res = json_decode($generateTokenRes->getBody(true)->getContents());
        $token = $res->token_type . ' ' . $res->access_token;

        $data = [
            'customer_id' => $paidRecord->payment_profile_id,
            'integrator_id' => config('payment.drivers.Paytrace.integrator_id'),
            'invoice_id' => $order->id,
            'transaction_id' => $paidRecord->transaction_id,
        ];

        $paytrace->voidTransaction($token, $data);
        $order->mailer()->sendVoidConfirmation();
        $output['status'] = true;
    }

    public function voidAnetPayment($paidRecord, $order)
    {
        $service = new PaymentService($this->cart->user, $this->cart);

        try {
            $response = $service->voidTransaction($paidRecord->transaction_id);
            $order->mailer()->sendVoidConfirmation();
            $output['status'] = true;
        } catch (AuthorizenetException $e) {
            $output['status'] = false;
            $output['message'] = 'Void has an issue';

            return $output;
        }
    }
}
