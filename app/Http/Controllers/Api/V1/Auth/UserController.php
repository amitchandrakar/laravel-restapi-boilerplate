<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Alonti\Auth\Cake\CakeHasher;
use App\Http\Controllers\Controller;
use App\Mail\ApplyHouseAccount;
use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Models\Category;
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
        $requestFromInvitee = config()->get('app.request-from-invitee');
        $categories = Category::getCategories($requestFromInvitee);
        view()->share('menuCategories', $categories);
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

        return view('user.profile', compact('user', 'states', 'deliveryAreaCount', 'deliveryAreaChosen', 'cafeList'));
    }

    public function orders()
    {
        Session::forget('cart-id-lock');
        // dd(Session::get('cart-id-lock'));

        $requestFromInvitee = config()->get('app.request-from-invitee');
        $categories = Category::getCategories($requestFromInvitee);

        view()->share('menuCategories', $categories);

        $userActiveCart = auth()->user()->active_cart_id ? Cart::find(auth()->user()->active_cart_id) : null;
        $activeOrders = auth()
            ->user()
            ->carts()
            ->whereNull('group_order_id')
            ->with(['order', 'order.time', 'shipping'])
            ->orderBy('id', 'asc')
            ->get();

        $activeGroupOrders = auth()
            ->user()
            ->carts()
            ->whereNotNull('group_order_id')
            ->with(['order', 'order.time', 'shipping'])
            ->orderBy('id', 'asc')
            ->get();

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

        $pastOrders = $pastOrders->map(function ($order) {
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

        return view(
            'user.orders',
            compact(
                'activeOrders',
                'pastOrders',
                'individualCart',
                'individualCartCount',
                'userActiveCart',
                'deliveryAreaCount',
                'deliveryAreaChosen',
                'cafeList',
                'activeGroupOrders'
            )
        );
    }

    public function viewOrder($hashid = null)
    {
        if ($hashid == null) {
            return redirect('/profile/orders')->with('notify-failure', 'Order is not found.');
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

        return view(
            'view-order',
            compact(
                'order',
                'items',
                'payments',
                'deliveryTimes',
                'deliveryAreaCount',
                'deliveryAreaChosen',
                'cafeList',
                'servingOption'
            )
        );
    }

    public function editGroupCartFromAdmin($cartId)
    {
        if ($cartId == null) {
            return redirect('/profile/orders')->with('notify-failure', 'Cart is not found.');
        }
        $cart = Cart::find($cartId);

        return redirect('/profile/edit-cart/' . $cart->encrypted_id);
    }

    public function editGroupCart($hashid = null)
    {
        if ($hashid == null) {
            return redirect('/profile/orders')->with('notify-failure', 'Cart is not found.');
        }

        $user = auth()->user();

        if ($user && $user->active_cart_id) {
            $cart = Cart::find($user->active_cart_id);
            if ($cart && $cart->order_id) {
                return redirect('/profile/orders')->with(
                    'notify-failure',
                    'Already some of your order is in edit mode. Please do update that and proceed.'
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

        return redirect('/summary');
    }

    public function editOrder($hashid = null)
    {
        if ($hashid == null) {
            return redirect('/profile/orders')->with('notify-failure', 'Order is not found.');
        }

        $userData = auth()->user();

        $order = Order::findByEncryptedId($hashid);

        if ($order->web) {
            return redirect('/profile/orders')->with(
                'notify-failure',
                'This order was placed by cafe manager, Please contact kitchen if you want to make any changes.'
            );
        }

        if (in_array($order->status, ['Delivered', 'Canceled'])) {
            return redirect('/profile/orders')->with('notify-failure', 'This order has been delivered.');
        }

        $cart = new Cart();
        $allowEditOrder = $cart->cartItemsAndOptionsCurrentStatus($order->cart->items);

        if (!$allowEditOrder) {
            return redirect('/profile/orders')->with(
                'notify-failure',
                'Some of your menu items are unavailable. Please contact your kitchen (' . $order->cafe->phone . ').'
            );
        }

        if ($userData->active_cart_id) {
            $cart = Cart::find($userData->active_cart_id);
            if ($cart && $cart->order_id && $cart->order_id != $order->id) {
                return redirect('/profile/orders')->with(
                    'notify-failure',
                    'Already some of your order is in edit mode. Please do update that and proceed.'
                );
            }
        }

        // Check if order can be edited (must be at least 24 hours before delivery)
        if ($order->hoursUntilDelivery() < 24) {
            return redirect('/profile/orders')->with(
                'notify-failure',
                'This order is locked hence you can not edit this order.'
            );
        }

        if ($order->cart->status) {
            return redirect('/summary');
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

            return redirect('/summary');
            // ->with('notify-failure', 'The listed item price will be updated if you change your delivery zipcode');
        } else {
            return redirect('/summary')->with('notify-failure', 'This order is already in edit mode.');
        }
    }

    public function updatePhone()
    {
        auth()
            ->user()
            ->update([
                'phone' => request('phone'),
            ]);

        return ['success' => true, 'message' => 'The record has been saved.'];
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

        return ['success' => true, 'message' => 'Address was saved successfully.'];
    }

    public function updateCmpyPhone()
    {
        auth()
            ->user()
            ->update([
                'secondary_phone' => request('secondary_phone'),
            ]);

        return ['success' => true, 'message' => 'The record has been saved.'];
    }

    public function updateSecondaryPhone()
    {
        auth()
            ->user()
            ->update([
                'secondary_phone' => request('secondary_phone'),
            ]);

        return ['success' => true, 'message' => 'The record has been saved.'];
    }

    public function updateSmsOptIn()
    {
        $smsOptIn = filter_var(request()->input('sms_opt_in', false), FILTER_VALIDATE_BOOLEAN);
        auth()
            ->user()
            ->update([
                'sms_opt_in' => $smsOptIn,
            ]);

        return ['success' => true, 'message' => 'The record has been saved.'];
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

        return ['success' => true, 'message' => 'The record has been saved.'];
    }

    public function attachHouseAccount()
    {
        return view('user.attach-house-account');
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
            session()->flash('notify-success', $msg);

            return response()->json(['success' => $sent, 'message' => $msg]);
        }

        return response()->json([
            'success' => $sent,
            'message' => 'There was a problem while send account application. Please make sure that provide email is valid.',
        ]);
    }

    public function updateLastName()
    {
        auth()
            ->user()
            ->update([
                'lname' => request('lname'),
            ]);

        return ['success' => true, 'message' => 'The record has been saved.'];
    }

    public function updateFirstName()
    {
        auth()
            ->user()
            ->update([
                'fname' => request('fname'),
            ]);

        return ['success' => true, 'message' => 'The record has been saved.'];
    }

    public function deleteCart($hashId)
    {
        if ($hashId == null) {
            return redirect('/profile/orders')->with('notify-failure', 'Cart is not found.');
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

            return redirect('/profile/orders')->with('notify-success', 'Your cart is deleted successfully');
        } else {
            return redirect('/profile/orders')->with(
                'notify-failure',
                'You can not delete your cart since order is placed for this cart'
            );
        }
    }

    public function cancelOrder($hashId = null)
    {
        if ($hashId == null) {
            return redirect('/profile/orders')->with('notify-failure', 'Order is not found.');
        }

        $order = Order::findByEncryptedId($hashId);

        if ($order->web) {
            return redirect('/profile/orders')->with(
                'notify-failure',
                'This order was placed by cafe manager, Please contact kitchen if you want to cancel.'
            );
        }

        // Check if order can be cancelled (must be at least 24 hours before delivery)
        if ($order->hoursUntilDelivery() < 24) {
            return redirect('/profile/orders')->with(
                'notify-failure',
                'This order is locked hence you can not cancel this order.'
            );
        }

        $order->status = 'Canceled';

        if ($order->save()) {
            $order->mailer()->sendOrderCanceled();

            return redirect('/profile/orders')->with('notify-success', 'Your order is canceled successfully');
        } else {
            return redirect('/profile/orders')->with('notify-failure', 'Something went wrong, please try again');
        }
    }

    public function invoiceDownload($hashId)
    {
        if (!$hashId) {
            return redirect('/profile/orders')->with('notify-failure', 'Order is not found.');
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
        $requestFromInvitee = config()->get('app.request-from-invitee');
        $categories = Category::getCategories($requestFromInvitee);
        view()->share('menuCategories', $categories);
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

        return view(
            'user.reward',
            compact(
                'user',
                'cashoutAmount',
                'deliveryAreaCount',
                'deliveryAreaChosen',
                'partialAmount',
                'rewardConfig',
                'rewardEmail',
                'rewards'
            )
        );
    }

    public function customerReferralRewards()
    {
        $requestFromInvitee = config()->get('app.request-from-invitee');
        $categories = Category::getCategories($requestFromInvitee);
        view()->share('menuCategories', $categories);
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

        return view(
            'user.referral_reward',
            compact('user', 'deliveryAreaCount', 'deliveryAreaChosen', 'allowRefer', 'referralConfig', 'referedEmails')
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

        return view('user.referred_customer', compact('deliveryAreaCount', 'deliveryAreaChosen', 'referredCustomers'));
    }
}
