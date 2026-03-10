<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Alonti\Amazon\AmazonService;
use App\Http\Controllers\Controller;
use App\Models\AmazonApiLog;
use App\Models\Cart;
use App\Models\Configuration;
use App\Models\CustomerReferral;
use App\Models\CustomerReferralReward;
use App\Models\Order;
use App\Models\Reward;
use App\Models\User;
use App\Models\UserConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function hasAccount(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request->has('email')) {
            $email = $request->get('email');
            $user = User::where(['email' => $email, 'type' => 0, 'group_id' => 5])->first();
            if ($user) {
                $data = [];
                $data['status'] = true;
                $data['account_status'] = $user->account_status;
                $data['manager_name'] = $user->cafe->csmUser->name;
                $data['manager_email'] = str_ireplace('@alonti.com', '', $user->cafe->csmUser->email) . '@alonti.com';
                $data['msg'] = 'Account exist!';
                $data['result'] = $user;
            }
        }

        return response()->json($data);
    }

    public function verifyAccount()
    {
        $credentials = request()->only('email', 'password');
        // $guestUserCart = $cartManager->getActiveCart(); // $cartManager is not defined here
        $guestUserCart = app(CartManager::class)->getActiveCart(); // Assuming CartManager is needed and should be resolved
        // $success = Auth::attempt($credentials, true);
        $success = Auth::attempt($credentials, true);
        $user = auth()->user();
        $msg = '';
        if ($success) {
            if ($user && $user->active_cart_id) {
                $existingActiveCart = Cart::find($user->active_cart_id);
                if ($existingActiveCart && $existingActiveCart->order_id) {
                    if (!in_array($existingActiveCart->order->status, ['Delivered', 'Canceled'])) {
                        $msg =
                            'Your completed order #' .
                            $existingActiveCart->order_id .
                            ' was edited by you and not updated. Please verify that was updated or not.';
                    }
                    $existingActiveCart->status = 0;
                    $existingActiveCart->save();
                    $user->active_cart_id = null;
                    $user->save();
                }
            }
            if ($guestUserCart) {
                $pendingIndividualCart = Cart::where([
                    'user_id' => auth()->user()->id,
                    'order_id' => null,
                    'group_order_id' => null,
                ])
                    ->where('id', '!=', $guestUserCart->id)
                    ->orderBy('id', 'desc')
                    ->get();
                if ($pendingIndividualCart && $pendingIndividualCart->count() > 0) {
                    $pendingIndividualCart->each(function (Cart $cart) {
                        $cart->discardCart();
                    });
                    if (!empty($msg)) {
                        $msg .= ' and your existing individual carts also discarded';
                    } else {
                        $msg = 'Your existing individual carts are discarded';
                    }
                }
                $guestUserCart->session_id = null;
                $guestUserCart->user_id = $user->id;
                $guestUserCart->save();
                $user->active_cart_id = $guestUserCart->id;
                $user->save();
            }
        }
        if ($msg) {
            return response(['status' => true, 'exception' => true, 'success' => $msg]);
        }

        return response(['status' => true, 'exception' => false, 'success' => $success]);
    }

    public function cashOut()
    {
        $request = request()->all();
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request['cashout_amount'] > 0) {
            $user = auth()->user();
            $data['status'] = true;
            $cashOutAmount = $request['cashout_amount'];
            $amazon = new AmazonService(request()->all());
            $result = $amazon->createCard();
            if ($result['status']) {
                $unpaidRewardData = app(Reward::class)->userUnPaidRewardValue($user->id)->sortBy('reward_value');
                $userRewardAmountToCashOut = 0;
                if ($unpaidRewardData->count() > 0) {
                    $unpaidRewardData->each(function (Reward $reward) use (&$userRewardAmountToCashOut) {
                        $userRewardAmountToCashOut += $reward->reward_value - $reward->paid_reward_value;
                    });
                }
                $this->updateRewards($unpaidRewardData, $userRewardAmountToCashOut, $cashOutAmount);
                $email = $request['amazon_email'];
                $user->mailer()->sendAmazonGiftCardEmail($email, $cashOutAmount, $result['gcClaimCode']);
                $data['status'] = true;
                $data['msg'] = 'Amazon gift card code will be sent to the recipient.';
            } else {
                $data['msg'] = $result['message'];
            }
        } else {
            $data['msg'] = 'Cash amount should be greater than $0';
        }

        return response()->json($data);
    }

    public function cancelGeneratedGiftCard(Request $request)
    {
        $request = request()->all();
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request->has('api_log_id')) {
            $api_log_id = $request->get('api_log_id');
            $info = AmazonApiLog::where('id', $api_log_id)->first();
            $amazon = new AmazonService(request()->all());
            $result = $amazon->cancelCard($info);
            if ($result['status']) {
                $data['status'] = true;
                $data['msg'] = 'Amazon gift card has been canceled successfully.';
            } else {
                $data['msg'] = $result['message'];
            }
        } else {
            $data['msg'] = 'Amazon request id is not exist!';
        }

        return response()->json($data);
    }

    public function updateRewards($unpaidRewardData, $userRewardAmountToCashOut, $cashAmount)
    {
        if (number_format((float) $userRewardAmountToCashOut, 2) == number_format((float) $cashAmount, 2)) {
            $unpaidRewardData->each(function (Reward $reward) {
                $reward->paid_reward_value = $reward->reward_value;
                $reward->paid_status = 2;
                $reward->update();
            });
        } else {
            $unpaidRewardData->each(function (Reward $reward) use (&$cashAmount) {
                if ($cashAmount == 0) {
                    return false;
                } else {
                    if ($reward->paid_reward_value == 0) {
                        if (number_format((float) $reward->reward_value, 2) == number_format((float) $cashAmount, 2)) {
                            $reward->paid_reward_value = $reward->reward_value;
                            $reward->paid_status = 2;
                            $reward->update();
                        } else {
                            $reward->paid_reward_value = $cashAmount;
                            $reward->paid_status = 1;
                            $reward->update();
                        }
                        $cashAmount = 0;
                    } else {
                        $rewardRemaining = $reward->reward_value - $reward->paid_reward_value;
                        if ($rewardRemaining > 0) {
                            if (number_format((float) $rewardRemaining, 2) == number_format((float) $cashAmount, 2)) {
                                $reward->paid_reward_value = $reward->paid_reward_value + $rewardRemaining;
                                $reward->paid_status = 2;
                                $reward->update();
                                $cashAmount = 0;
                            } else {
                                if (
                                    number_format((float) $rewardRemaining, 2) > number_format((float) $cashAmount, 2)
                                ) {
                                    if (
                                        number_format((float) $reward->reward_value, 2) ==
                                        number_format((float) ($reward->paid_reward_value + $cashAmount), 2)
                                    ) {
                                        $reward->paid_reward_value = $reward->paid_reward_value + $cashAmount;
                                        $reward->paid_status = 2;
                                        $reward->update();
                                    } else {
                                        $reward->paid_reward_value = $reward->paid_reward_value + $cashAmount;
                                        $reward->paid_status = 1;
                                        $reward->update();
                                    }
                                    $cashAmount = 0;
                                } else {
                                    $reward->paid_reward_value = $reward->paid_reward_value + $rewardRemaining;
                                    $reward->paid_status = 2;
                                    $reward->update();
                                    $cashAmount -= $rewardRemaining;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    public function updateUserRewardConfig(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request->has('reward_config')) {
            $rewardConfig = $request->get('reward_config');
            if ($rewardConfig && $request->get('reward_email') == '') {
                $data['msg'] = 'Please enter valid email address to receive gift card.';
            } else {
                $user = auth()->user();
                $data = [
                    'user_id' => $user->id,
                    'alonti_rewards' => $rewardConfig,
                    'reward_email' => $request->get('reward_email'),
                    'created_by' => 0,
                ];
                if (!$user->myconfig) {
                    UserConfiguration::create($data);
                } else {
                    $user->myconfig->update($data);
                }
                $data['status'] = true;
                $data['msg'] = $rewardConfig
                    ? 'You have successfully signed up to the Alonti rewards program'
                    : 'You have opted out from Alonti rewards program.';
            }
        } else {
            $data['msg'] = 'Request data not exist';
        }

        return response()->json($data);
    }

    public function referCustomers(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request->has('emails')) {
            $emails = $request->get('emails');
            $user = auth()->user();
            $registeredCustomers = [];
            $registeredCustomersRecord = false;
            $referredCustomers = [];
            $referredCustomersRecord = false;
            $storedCustomerEmailList = [];
            $referralConfig = Configuration::where([
                'column_key' => 'referral-reward-value',
                'field_key' => 'referral-range-value',
            ])->first();
            $referralRewardAmount = $referralConfig->column_value;
            $referralMinimumFoodPurchaseAmount = $referralConfig->field_value;
            $cafeId = session()->has('UserDeliveryInformation.alontiDeliveryArea')
                ? session()->get('UserDeliveryInformation.alontiDeliveryArea.cafe.id')
                : null;
            $districtId = session()->has('UserDeliveryInformation.alontiDeliveryArea')
                ? session()->get('UserDeliveryInformation.alontiDeliveryArea.cafe.district_id')
                : null;
            if (!empty($emails)) {
                foreach ($emails as $key => $values) {
                    $userRecord = User::where('email', $values['email'])->count();
                    if (!$userRecord) {
                        $referralRecord = CustomerReferral::where('email', $values['email'])->count();
                        if (!$referralRecord) {
                            $data = [
                                'user_id' => $user->id,
                                'cafe_id' => $cafeId,
                                'district_id' => $districtId,
                                'name' => $values['name'],
                                'email' => $values['email'],
                            ];
                            CustomerReferral::create($data);
                            $user
                                ->mailer()
                                ->sendReferralEmail(
                                    $values['email'],
                                    $referralRewardAmount,
                                    $referralMinimumFoodPurchaseAmount
                                );
                            $storedCustomerEmailList[] = $values['email'];
                        } else {
                            $referredCustomers[] = $values['email'];
                            $referredCustomersRecord = true;
                        }
                    } else {
                        $registeredCustomers[] = $values['email'];
                        $registeredCustomersRecord = true;
                    }
                }
                if (!empty($storedCustomerEmailList)) {
                    $user->mailer()->sendReferralEmailListToCsm($storedCustomerEmailList);
                }
                $data['status'] = true;
                $data['msg'] = 'You have successfully referred customers';
                $data['registeredCustomersRecord'] = $registeredCustomersRecord;
                $data['registeredCustomersRecordList'] = $registeredCustomers;
                $data['referredCustomersRecord'] = $referredCustomersRecord;
                $data['referredCustomersRecordList'] = $referredCustomers;
                $data['storedCustomerEmailList'] = $storedCustomerEmailList;
            } else {
                $data['msg'] = 'Enter emails';
            }
        }

        return response()->json($data);
    }

    public function referralCashOut()
    {
        $request = request()->all();
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';
        if ($request['cashout_amount'] > 0) {
            $user = auth()->user();
            $unpaidReferralRewardData = app(CustomerReferralReward::class)
                ->userUnPaidReferralRewardValue($user->id)
                ->sortBy('customer_rewards');
            $data['status'] = true;
            $cashOutAmount = $request['cashout_amount'];
            $amazon = new AmazonService(request()->all());
            $result = $amazon->createCard('referral');
            if ($result['status']) {
                $userReferralRewardAmountToCashOut = 0;
                if ($unpaidReferralRewardData->count() > 0) {
                    $unpaidReferralRewardData->each(function (CustomerReferralReward $reward) use (
                        &$userReferralRewardAmountToCashOut
                    ) {
                        $userReferralRewardAmountToCashOut += $reward->customer_rewards - $reward->paid_reward_value;
                    });
                }
                $this->updateReferralRewards(
                    $unpaidReferralRewardData,
                    $userReferralRewardAmountToCashOut,
                    $cashOutAmount
                );
                $email = $request['amazon_email'];
                $user->mailer()->sendAmazonGiftCardEmail($email, $cashOutAmount, $result['gcClaimCode'], 'referral');
                $data['status'] = true;
                $data['msg'] = 'Amazon gift card code will be sent to the recipient.';
            } else {
                $data['msg'] = $result['message'];
            }
        } else {
            $data['msg'] = 'Cash amount should be greater than $0';
        }

        return response()->json($data);
    }

    public function updateReferralRewards($unpaidReferralRewardData, $userReferralRewardAmountToCashOut, $cashOutAmount)
    {
        if (number_format((float) $userReferralRewardAmountToCashOut, 2) == number_format((float) $cashOutAmount, 2)) {
            $unpaidReferralRewardData->each(function (CustomerReferralReward $reward) {
                $reward->paid_reward_value = $reward->customer_rewards;
                $reward->paid_status = 2;
                $reward->update();
            });
        } else {
            $unpaidReferralRewardData->each(function (CustomerReferralReward $reward) use (&$cashOutAmount) {
                if ($cashOutAmount == 0) {
                    return false;
                } else {
                    if ($reward->paid_reward_value == 0) {
                        if (
                            number_format((float) $reward->customer_rewards, 2) ==
                            number_format((float) $cashOutAmount, 2)
                        ) {
                            $reward->paid_reward_value = $reward->customer_rewards;
                            $reward->paid_status = 2;
                            $reward->update();
                        } else {
                            $reward->paid_reward_value = $cashOutAmount;
                            $reward->paid_status = 1;
                            $reward->update();
                        }
                        $cashOutAmount = 0;
                    } else {
                        $rewardRemaining = $reward->customer_rewards - $reward->paid_reward_value;
                        if ($rewardRemaining > 0) {
                            if (
                                number_format((float) $rewardRemaining, 2) == number_format((float) $cashOutAmount, 2)
                            ) {
                                $reward->paid_reward_value = $reward->paid_reward_value + $rewardRemaining;
                                $reward->paid_status = 2;
                                $reward->update();
                                $cashOutAmount = 0;
                            } else {
                                if (
                                    number_format((float) $rewardRemaining, 2) >
                                    number_format((float) $cashOutAmount, 2)
                                ) {
                                    if (
                                        number_format((float) $reward->customer_rewards, 2) ==
                                        number_format((float) ($reward->paid_reward_value + $cashOutAmount), 2)
                                    ) {
                                        $reward->paid_reward_value = $reward->paid_reward_value + $cashOutAmount;
                                        $reward->paid_status = 2;
                                        $reward->update();
                                    } else {
                                        $reward->paid_reward_value = $reward->paid_reward_value + $cashOutAmount;
                                        $reward->paid_status = 1;
                                        $reward->update();
                                    }
                                    $cashOutAmount = 0;
                                } else {
                                    $reward->paid_reward_value = $reward->paid_reward_value + $rewardRemaining;
                                    $reward->paid_status = 2;
                                    $reward->update();
                                    $cashOutAmount -= $rewardRemaining;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    /**
     * Delete customer's address
     *
     * return json response
     */
    public function deleteAddress()
    {
        $addrId = request()->addrId;
        $address = request()->address;

        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, Please try again!';

        if ($addrId && $address) {
            $updateAddress = Order::where([
                'alonti_user_id' => Auth::user()->id,
                'address_status' => 1,
            ])
                ->where(DB::raw('TRIM(LOWER(d_addr))'), trim(strtolower($address)))
                ->update(['address_status' => 0]);

            if ($updateAddress) {
                $data['status'] = true;
                $data['msg'] = 'Address has been deleted successfully';
            }
        }

        return response()->json($data);
    }
}
