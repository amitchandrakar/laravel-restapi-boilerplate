<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Alonti\Auth\Cake\CakeHasher;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\Api\V1\CustomerRewardsResource;
use App\Http\Resources\Api\V1\DeliveryAreasResource;
use App\Http\Resources\Api\V1\OrderDetailResource;
use App\Http\Resources\Api\V1\OrdersListResource;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Http\Resources\Api\V1\RedirectResource;
use App\Http\Resources\Api\V1\ReferralRewardsResource;
use App\Mail\ApplyHouseAccount;
use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Models\Configuration;
use App\Models\CustomerReferral;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReferralSalesArea;
use App\Models\Reward;
use App\Models\State;
use App\Models\Time;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    /**
     * HTTP client for external API calls
     *
     * @var \GuzzleHttp\Client
     */
    public $client;

    public function profile()
    {
        $user = auth()->user()->toArray();
        unset($user['password']);
        $states = State::all();
        $deliveryAreaCount = session()->has('UserDeliveryInformation.alontiDeliveryAreaCount')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaCount')
            : 0;
        $deliveryAreaChosen = session()->has('UserDeliveryInformation.deliveryAreaChosen')
            ? session()->get('UserDeliveryInformation.deliveryAreaChosen')
            : false;
        $cafeList = session()->has('UserDeliveryInformation.alontiDeliveryAreaList')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaList')
            : [];

        return $this->successResponse(
            ProfileResource::make([
                'user' => $user,
                'states' => $states,
                'delivery_area_count' => $deliveryAreaCount,
                'delivery_area_chosen' => $deliveryAreaChosen,
                'cafe_list' => $cafeList,
            ]),
            'Success'
        );
    }

    public function orders()
    {
        Session::forget('cart-id-lock');

        $userActiveCart = auth()->user()->active_cart_id ? Cart::find(auth()->user()->active_cart_id) : null;
        $activeOrders = auth()
            ->user()
            ->carts()
            ->whereNull('group_order_id')
            ->with(['order', 'order.time', 'shipping'])
            ->orderBy('id', 'asc')
            ->get();
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cart> $activeOrders */
        $activeGroupOrders = auth()
            ->user()
            ->carts()
            ->whereNotNull('group_order_id')
            ->with(['order', 'order.time', 'shipping'])
            ->orderBy('id', 'asc')
            ->get();
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cart> $activeGroupOrders */
        $individualCart = Cart::individual()->mine()->pending()->first();
        $individualCartCount = $individualCart ? $individualCart->items()->count() : 0;

        $activeOrders = $activeOrders
            ->filter(function (Cart $cart) {
                $skipCart = $cart->order && in_array($cart->order->status, ['Delivered', 'Canceled']);

                return $skipCart ? false : !$cart->group_order_id || $cart->items()->count() >= 0;
            })
            ->values();

        $activeGroupOrders = $activeGroupOrders
            ->filter(function (Cart $cart) {
                $skipCart = $cart->order && in_array($cart->order->status, ['Delivered', 'Canceled']);

                return $skipCart ? false : $cart->group_order_id || $cart->items()->count() >= 0;
            })
            ->values();

        $activeOrders = $activeOrders
            ->map(function (Cart $cart) {
                $editStatus = true;
                $cancelStatus = true;

                if ($cart->order) {
                    $hoursUntilDelivery = $cart->order->hoursUntilDelivery();
                    $editStatus = $hoursUntilDelivery < 24 ? false : true;
                }

                $status = 'Not Ordered';

                if ($cart->order) {
                    if ($cart->status) {
                        $status = 'Order is in edit mode';
                    } else {
                        $status = $cart->order->status;
                    }
                }

                if ($cart->order && $cart->order->web) {
                    $cancelStatus = false;
                }

                $deliveryTime = '-';
                $deliveryDate = '-';

                if ($cart->order) {
                    $deliveryTime = $cart->order->time->time;
                    $deliveryDate = Carbon::parse($cart->order->d_date)->format('m/d/Y');
                } elseif ($cart->shipping) {
                    $deliveryTime = $cart->shipping->delivery_time ? $cart->shipping->time->time : '-';
                    $deliveryDate =
                        $cart->shipping->delivery_date != ''
                            ? date('m/d/Y', strtotime($cart->shipping->delivery_date))
                            : '-';
                }

                return [
                    'cart_id' => $cart->id,
                    'cart_hash_id' => $cart->encrypted_id,
                    'total' => $cart->order ? '$' . round($cart->order->total, 2) : round($cart->total, 2),
                    'order_type' => 'Catering',
                    'status' => $status,
                    'order_name' => $cart->order_name ? $cart->order_name : '-',
                    'delivery_date' => $deliveryDate,
                    'order_id' => $cart->order ? $cart->order->id : '-',
                    'order_hash_id' => $cart->order ? $cart->order->encrypted_id : '',
                    'delivery_time' => $deliveryTime,
                    'payment_type' => $cart->payment ? $cart->payment->terms : '-',
                    'cart_status' => $cart->status,
                    'edit_status' => $editStatus,
                    'cancel_status' => $cancelStatus,
                    'group_cart' => $cart->group_order_id ? true : false,
                ];
            })
            ->all();

        $activeGroupOrders = $activeGroupOrders
            ->map(function (Cart $cart) {
                $editStatus = true;
                $cancelStatus = true;

                if ($cart->order) {
                    $hoursUntilDelivery = $cart->order->hoursUntilDelivery();
                    $editStatus = $hoursUntilDelivery < 24 ? false : true;
                }

                $status = 'Not Ordered';

                if ($cart->order) {
                    if ($cart->status) {
                        $status = 'Order is in edit mode';
                    } else {
                        $status = $cart->order->status;
                    }
                }

                if ($cart->order && $cart->order->web) {
                    $cancelStatus = false;
                }

                $deliveryTime = '-';
                $deliveryDate = '-';

                if ($cart->order) {
                    $deliveryTime = $cart->order->time->time;
                    $deliveryDate = Carbon::parse($cart->order->d_date)->format('m/d/Y');
                } elseif ($cart->shipping) {
                    $deliveryTime = $cart->shipping->delivery_time ? $cart->shipping->time->time : '-';
                    $deliveryDate =
                        $cart->shipping->delivery_date != ''
                            ? Carbon::parse($cart->shipping->delivery_date)->format('m/d/Y')
                            : '-';
                }

                return [
                    'cart_id' => $cart->id,
                    'cart_hash_id' => $cart->encrypted_id,
                    'total' => $cart->order ? '$' . round($cart->order->total, 2) : '$' . round($cart->total, 2),
                    'order_type' => 'Group',
                    'status' => $status,
                    'order_name' => $cart->order_name ? $cart->order_name : '-',
                    'delivery_date' => $deliveryDate,
                    'order_id' => $cart->order ? $cart->order->id : '-',
                    'order_hash_id' => $cart->order ? $cart->order->encrypted_id : '',
                    'delivery_time' => $deliveryTime,
                    'payment_type' => $cart->payment ? $cart->payment->terms : '-',
                    'cart_status' => $cart->status,
                    'edit_status' => $editStatus,
                    'cancel_status' => $cancelStatus,
                    'group_cart' => $cart->group_order_id ? true : false,
                    'gid' => $cart->group_order_id ? $cart->group_order_id : null,
                    'gcid' => $cart->groupOrderConfig ? $cart->groupOrderConfig->id : null,
                ];
            })
            ->all();

        $orderStatus = ['Delivered', 'Canceled'];
        $pastOrders = auth()->user()->orders()->whereIn('status', $orderStatus)->with('time')->get();
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $pastOrders */
        $pastOrders = $pastOrders->map(function (\App\Models\Order $order) {
            return [
                'total' => '$' . round($order->total, 2),
                'order_type' => $order->group_order_id ? 'Group Order' : 'Catering',
                'status' => $order->status,
                'order_name' => $order->orderName,
                'delivery_date' => Carbon::parse($order->d_date)->format('m/d/Y'),
                'order_id' => $order->id,
                'order_hash_id' => $order->encrypted_id,
                'delivery_time' => $order->time->time,
                'payment_type' => $order->payment ? $order->payment->terms : '-',
                'reorder' => $order->is_new_order && !$order->group_order_id ? true : false,
            ];
        });

        $deliveryAreaCount = session()->has('UserDeliveryInformation.alontiDeliveryAreaCount')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaCount')
            : 0;
        $deliveryAreaChosen = session()->has('UserDeliveryInformation.deliveryAreaChosen')
            ? session()->get('UserDeliveryInformation.deliveryAreaChosen')
            : false;
        $cafeList = session()->has('UserDeliveryInformation.alontiDeliveryAreaList')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaList')
            : [];

        return $this->successResponse(
            OrdersListResource::make([
                'active_orders' => $activeOrders,
                'past_orders' => $pastOrders,
                'individual_cart' => $individualCart,
                'individual_cart_count' => $individualCartCount,
                'user_active_cart' => $userActiveCart,
                'delivery_area_count' => $deliveryAreaCount,
                'delivery_area_chosen' => $deliveryAreaChosen,
                'cafe_list' => $cafeList,
                'active_group_orders' => $activeGroupOrders,
            ]),
            'Success'
        );
    }

    public function viewOrder($hashid = null)
    {
        if ($hashid == null) {
            return $this->notFoundResponse('Order is not found.');
        }

        $order = Order::findByEncryptedId($hashid);
        $payments = Payment::pluck('terms', 'id')->toArray();
        $deliveryTimes = Time::pluck('time', 'id')->toArray();
        $items = $order->cart->items()->withoutAddons()->with('addons')->get();
        $deliveryAreaCount = session()->has('UserDeliveryInformation.alontiDeliveryAreaCount')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaCount')
            : 0;
        $deliveryAreaChosen = session()->has('UserDeliveryInformation.deliveryAreaChosen')
            ? session()->get('UserDeliveryInformation.deliveryAreaChosen')
            : false;
        $cafeList = session()->has('UserDeliveryInformation.alontiDeliveryAreaList')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaList')
            : [];

        // Get serving ware options from offmenu table having cart_id = $cartInfo->id
        $servingOption = DB::table('offmenus')
            ->join('serving_options', 'serving_options.id', '=', 'offmenus.serving_option_id')
            ->select('serving_options.name', 'serving_options.id', 'offmenus.price', 'offmenus.qty')
            ->where('offmenus.order_id', $order->id)
            ->first();

        return $this->successResponse(
            OrderDetailResource::make([
                'order' => $order,
                'items' => $items,
                'payments' => $payments,
                'delivery_times' => $deliveryTimes,
                'delivery_area_count' => $deliveryAreaCount,
                'delivery_area_chosen' => $deliveryAreaChosen,
                'cafe_list' => $cafeList,
                'serving_option' => $servingOption,
            ]),
            'Success'
        );
    }

    public function editGroupCartFromAdmin($cartId)
    {
        if ($cartId == null) {
            return $this->notFoundResponse('Cart is not found.');
        }
        $cart = Cart::find($cartId);
        if (!$cart) {
            return $this->notFoundResponse('Cart is not found.');
        }

        return $this->successResponse(
            RedirectResource::make(['redirect' => '/profile/edit-cart/' . $cart->encrypted_id]),
            'Success'
        );
    }

    public function editGroupCart($hashid = null)
    {
        if ($hashid == null) {
            return $this->notFoundResponse('Cart is not found.');
        }

        $user = auth()->user();

        if ($user && $user->active_cart_id) {
            $cart = Cart::find($user->active_cart_id);
            if ($cart && $cart->order_id) {
                return $this->errorResponse(
                    'Already some of your order is in edit mode. Please do update that and proceed.',
                    409
                );
            }
        }

        $individualCart = Cart::individual()->mine()->pending()->first();
        if ($individualCart) {
            $individualCart->discardCart();
        }

        $cart = Cart::findByEncryptedId($hashid);

        if (!$cart->abandonedCart) {
            $data = [
                'cart_id' => $cart->id,
                'alonti_user_id' => $cart->user_id,
                'cafe_id' => $cart->cafe_id,
            ];
            AbandonedCart::create($data);
        }

        if ($user) {
            $user = $user->fresh();
            $user->active_cart_id = $cart->id;
            $user->save();
        }

        $deliveryInfo = session()->has('UserDeliveryInformation.alontiDeliveryArea')
            ? session()->get('UserDeliveryInformation.alontiDeliveryArea')
            : null;

        if ($deliveryInfo) {
            $cart->updateDeliveryArea($deliveryInfo->zipcode, $deliveryInfo->state_id, $deliveryInfo->cafe->id);
        }

        return $this->successResponse(RedirectResource::make(['redirect' => '/summary']), 'Success');
    }

    public function editOrder($hashid = null)
    {
        if ($hashid == null) {
            return $this->notFoundResponse('Order is not found.');
        }

        $userData = auth()->user();

        $order = Order::findByEncryptedId($hashid);

        if ($order->web) {
            return $this->errorResponse(
                'This order was placed by cafe manager, Please contact kitchen if you want to make any changes.',
                403
            );
        }

        if (in_array($order->status, ['Delivered', 'Canceled'])) {
            return $this->errorResponse('This order has been delivered.', 403);
        }

        $cart = new Cart();
        $allowEditOrder = $cart->cartItemsAndOptionsCurrentStatus($order->cart->items);

        if (!$allowEditOrder) {
            return $this->errorResponse(
                'Some of your menu items are unavailable. Please contact your kitchen (' . $order->cafe->phone . ').',
                400
            );
        }

        if ($userData->active_cart_id) {
            $cart = Cart::find($userData->active_cart_id);
            if ($cart && $cart->order_id && $cart->order_id != $order->id) {
                return $this->errorResponse(
                    'Already some of your order is in edit mode. Please do update that and proceed.',
                    409
                );
            }
        }

        // Check if order can be edited (must be at least 24 hours before delivery)
        if ($order->hoursUntilDelivery() < 24) {
            return $this->errorResponse('This order is locked hence you can not edit this order.', 403);
        }

        if ($order->cart->status) {
            return $this->successResponse(RedirectResource::make(['redirect' => '/summary']), 'Success');
        }

        if (auth()->user()->active_cart_id != $order->cart->id) {
            $individualCart = Cart::individual()->mine()->pending()->first();
            if ($individualCart) {
                $individualCart->discardCart();
            }
            $userRecord['active_cart_id'] = $order->cart->id;
            $order->user->update($userRecord);

            $order->status = 'Ordered';
            $order->save();

            $cartData = Cart::find($order->cart->id);
            $cartData->order_status = $order->status;
            $cartData->status = 1;
            $cartData->save();

            $deliveryInfo = session()->get('UserDeliveryInformation.alontiDeliveryArea');
            $cartData->updateDeliveryArea($deliveryInfo->zipcode, $deliveryInfo->state_id, $deliveryInfo->cafe->id);

            return $this->successResponse(RedirectResource::make(['redirect' => '/summary']), 'Success');
        }

        return $this->errorResponse('This order is already in edit mode.', 409);
    }

    public function updatePhone()
    {
        auth()
            ->user()
            ->update([
                'phone' => request('phone'),
            ]);

        return $this->successResponse(null, 'The record has been saved.');
    }

    public function updateAddress()
    {
        $info = request()->only(['address', 'address_two', 'city', 'zip', 'stateSelected']);
        $state = State::find($info['stateSelected']);

        $address = $info['address'] . ' ' . $info['city'] . ' ' . $state->name . ' ' . $info['zip'];
        $APIKey = env('GoogleMap');
        $authUrl =
            'https://maps.googleapis.com/maps/api/geocode/json?key=' . $APIKey . '&address=' . urlencode($address);
        $this->client = new \GuzzleHttp\Client();
        $response = $this->client->get($authUrl);
        $latLong = json_decode($response->getBody(true)->getContents());

        auth()
            ->user()
            ->update([
                'addr' => $info['address'],
                'addr2' => $info['address_two'],
                'city' => $info['city'],
                'state' => $state ? $state->name : '',
                'zip' => $info['zip'],
                'physical_addr' => $info['address'],
                'physical_addr2' => $info['address_two'],
                'physical_state' => $info['city'],
                'physical_city' => $state ? $state->name : '',
                'physical_zip' => $info['zip'],
                'latitude' => isset($latLong->results[0]->geometry->location)
                    ? $latLong->results[0]->geometry->location->lat
                    : auth()->user()->latitude,
                'longitude' => isset($latLong->results[0]->geometry->location)
                    ? $latLong->results[0]->geometry->location->lng
                    : auth()->user()->longitude,
            ]);

        return $this->successResponse(null, 'Address was saved successfully.');
    }

    public function updateCmpyPhone()
    {
        auth()
            ->user()
            ->update([
                'secondary_phone' => request('secondary_phone'),
            ]);

        return $this->successResponse(null, 'The record has been saved.');
    }

    public function updateSecondaryPhone()
    {
        auth()
            ->user()
            ->update([
                'secondary_phone' => request('secondary_phone'),
            ]);

        return $this->successResponse(null, 'The record has been saved.');
    }

    public function updateSmsOptIn()
    {
        $smsOptIn = filter_var(request()->input('sms_opt_in', false), FILTER_VALIDATE_BOOLEAN);
        auth()
            ->user()
            ->update([
                'sms_opt_in' => $smsOptIn,
            ]);

        return $this->successResponse(null, 'The record has been saved.');
    }

    public function updatePassword(CakeHasher $hasher)
    {
        if (request('password')) {
            $hashed_password = $hasher->make(request('password'));
            auth()
                ->user()
                ->update([
                    'password' => $hashed_password,
                ]);
        }

        return $this->successResponse(null, 'The record has been saved.');
    }

    public function attachHouseAccount()
    {
        return $this->successResponse(null, 'Attach house account form data.');
    }

    public function applyHouseAccount()
    {
        $sent = false;

        try {
            Mail::to(request('email'))->send(
                new ApplyHouseAccount([
                    'customer_name' => auth()->user()->name,
                    'customer_email' => auth()->user()->email,
                    'company_name' => request('company'),
                    'approver_name' => request('approver'),
                    'approver_email' => request('email'),
                ])
            );

            $sent = true;

            // Save the email log
            storeEmailSentLogs(
                auth()->user()->email,
                request('email'),
                '',
                'Apply House Account',
                'Apply House Account'
            );
        } catch (\Exception $e) {
            $sent = false;
        }

        if ($sent) {
            $msg = 'Your account application has been sent successfully.';

            return $this->successResponse(null, $msg);
        }

        return $this->errorResponse(
            'There was a problem while send account application. Please make sure that provide email is valid.',
            400
        );
    }

    public function updateLastName()
    {
        auth()
            ->user()
            ->update([
                'lname' => request('lname'),
            ]);

        return $this->successResponse(null, 'The record has been saved.');
    }

    public function updateFirstName()
    {
        auth()
            ->user()
            ->update([
                'fname' => request('fname'),
            ]);

        return $this->successResponse(null, 'The record has been saved.');
    }

    public function deleteCart($hashId)
    {
        if ($hashId == null) {
            return $this->notFoundResponse('Cart is not found.');
        }

        $cart = Cart::findByEncryptedId($hashId);
        $user = auth()->user();
        $user = $user->fresh();

        if (!$cart->order_id) {
            $cartId = $cart->id;

            if ($cart->group_order_id) {
                $cart->invitees->each(function ($cartInvitee) {
                    $cartInvitee->delete();
                });
            }

            $abandonedCart = $cart->abandonedCart ? $cart->abandonedCart : null;
            $cart->discardCart();

            if ($abandonedCart) {
                $abandonedCart->delete();
            }

            if ($cartId == $user->active_cart_id) {
                $user->active_cart_id = null;
                $user->save();
            }

            return $this->successResponse(
                RedirectResource::make(['redirect' => '/profile/orders']),
                'Your cart is deleted successfully'
            );
        }

        return $this->errorResponse('You can not delete your cart since order is placed for this cart', 403);
    }

    public function cancelOrder($hashId = null)
    {
        if ($hashId == null) {
            return $this->notFoundResponse('Order is not found.');
        }

        $order = Order::findByEncryptedId($hashId);

        if ($order->web) {
            return $this->errorResponse(
                'This order was placed by cafe manager, Please contact kitchen if you want to cancel.',
                403
            );
        }

        // Check if order can be cancelled (must be at least 24 hours before delivery)
        if ($order->hoursUntilDelivery() < 24) {
            return $this->errorResponse('This order is locked hence you can not cancel this order.', 403);
        }

        $order->status = 'Canceled';

        if ($order->save()) {
            $order->mailer()->sendOrderCanceled();

            return $this->successResponse(
                RedirectResource::make(['redirect' => '/profile/orders']),
                'Your order is canceled successfully'
            );
        }

        return $this->errorResponse('Something went wrong, please try again', 500);
    }

    public function invoiceDownload($hashId)
    {
        if (!$hashId) {
            return $this->notFoundResponse('Order is not found.');
        }

        $order = Order::findByEncryptedId($hashId);
        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option('enable_php', true);
        $data = ['order' => $order, 'pdf' => $pdf];
        $pdf->loadView('mails.pdf.invoice', $data);
        $canvas = $pdf->getDomPDF()->getCanvas();
        $text = 'Invoice #' . $order->id;
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('Helvetica');
        $size = 12;
        $x = 10;
        $y = 10;
        $canvas->page_text($x, $y, $text, $font, $size);

        return $pdf->download('Invoice_' . $order->id . '.pdf');
    }

    public function customerRewards()
    {
        $user = auth()->user();
        $states = State::all();
        $deliveryAreaCount = session()->has('UserDeliveryInformation.alontiDeliveryAreaCount')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaCount')
            : 0;
        $deliveryAreaChosen = session()->has('UserDeliveryInformation.deliveryAreaChosen')
            ? session()->get('UserDeliveryInformation.deliveryAreaChosen')
            : false;
        $cashoutAmount = app(Reward::class)->userCashOutAmount($user->id);
        $partialAmount = ['5' => '$5', '10' => '$10', '20' => '$20', '50' => '$50', 'all' => 'All'];
        $rewardConfig = $user->myconfig && $user->myconfig->alonti_rewards ? true : false;
        $rewardEmail = $user->myconfig ? $user->myconfig->reward_email : $user->email;
        $rewards = app(Reward::class)->earnedAmazonRewardHistory($user->id);

        return $this->successResponse(
            CustomerRewardsResource::make([
                'user' => $user,
                'cashout_amount' => $cashoutAmount,
                'delivery_area_count' => $deliveryAreaCount,
                'delivery_area_chosen' => $deliveryAreaChosen,
                'partial_amount' => $partialAmount,
                'reward_config' => $rewardConfig,
                'reward_email' => $rewardEmail,
                'rewards' => $rewards,
            ]),
            'Success'
        );
    }

    public function customerReferralRewards()
    {
        $user = auth()->user();
        $states = State::all();
        $deliveryAreaCount = session()->has('UserDeliveryInformation.alontiDeliveryAreaCount')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaCount')
            : 0;
        $deliveryAreaChosen = session()->has('UserDeliveryInformation.deliveryAreaChosen')
            ? session()->get('UserDeliveryInformation.deliveryAreaChosen')
            : false;
        $salesAreaId = session()->has('UserDeliveryInformation.alontiDeliveryArea')
            ? session()->get('UserDeliveryInformation.alontiDeliveryArea.cafe.district_id')
            : null;
        $allowRefer = false;

        if ($salesAreaId) {
            $salesArea = ReferralSalesArea::where(['sales_area_id' => $salesAreaId])->first();
            if ($salesArea && $salesArea->available) {
                $allowRefer = true;
            }
        }

        $allowRefer = true;
        $referralConfig = Configuration::where([
            'column_key' => 'referral-reward-value',
            'field_key' => 'referral-range-value',
        ])->first();

        $referedEmails = CustomerReferral::where(['user_id' => $user->id])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            ReferralRewardsResource::make([
                'user' => $user,
                'delivery_area_count' => $deliveryAreaCount,
                'delivery_area_chosen' => $deliveryAreaChosen,
                'allow_refer' => $allowRefer,
                'referral_config' => $referralConfig,
                'refered_emails' => $referedEmails,
            ]),
            'Success'
        );
    }

    public function referredCustomerStatus()
    {
        $user = auth()->user();

        $customerData = CustomerReferral::where(['user_id' => $user->id])
            ->with([
                'customerReferralRewards' => function ($q) use ($user) {
                    return $q->where(['user_id' => $user->id]);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $referredCustomers = [];

        $customerData->each(function (CustomerReferral $referral) use (&$referredCustomers) {
            $referredCustomers[$referral->id]['email'] = $referral->email;
            $referredCustomers[$referral->id]['registered'] = $referral->registered ? 'Yes' : 'No';
            $referredCustomers[$referral->id]['order_placed'] = $referral->order_placed ? 'Yes' : 'No';
            $referredCustomers[$referral->id]['order_status'] = isset($referral->customerReferralRewards[0])
                ? $referral->customerReferralRewards[0]['order']->status
                : 'N/A';
            $referredCustomers[$referral->id]['earned_rewards'] = isset($referral->customerReferralRewards[0])
                ? '$' . round($referral->customerReferralRewards[0]->customer_rewards, 2)
                : 'N/A';
        });

        $referredCustomers = array_values($referredCustomers);
        $deliveryAreaCount = session()->has('UserDeliveryInformation.alontiDeliveryAreaCount')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaCount')
            : 0;
        $deliveryAreaChosen = session()->has('UserDeliveryInformation.deliveryAreaChosen')
            ? session()->get('UserDeliveryInformation.deliveryAreaChosen')
            : false;

        return $this->successResponse(
            DeliveryAreasResource::make([
                'delivery_area_count' => $deliveryAreaCount,
                'delivery_area_chosen' => $deliveryAreaChosen,
                'referred_customers' => $referredCustomers,
            ]),
            'Success'
        );
    }
}
