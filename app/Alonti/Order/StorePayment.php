<?php

declare(strict_types=1);


namespace App\Alonti\Order;

use App\Alonti\Cart\CartManager;
use App\Alonti\PaymentService\PaymentService;
use App\Alonti\PaymentService\PaytraceService;
use App\Models\Billing;
use App\Models\Cim;
use App\Models\CimPaymentProfile;
use App\Models\Configuration;
use App\Models\Payment;
use App\Models\PaytraceApiLog;
use App\Models\Reward;
use App\Models\State;
use App\Models\UserCreditCardAddress;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StorePayment
{
    public function __construct($data)
    {
        $this->data = collect($data);
        $this->cart = app(CartManager::class)->getActiveCart();
        $this->payments = Payment::select('terms', 'id')->get();
    }

    public function paymentTypeValidation()
    {
        $validate['status'] = true;
        $validate['msg'] = 'success';
        if (!$this->data->get('paymentType')) {
            $validate['status'] = false;
            $validate['msg'] = 'Please select your payment type';
        } else {
            if ($this->data->get('isCardSelected')) {
                if (!$this->data->get('existPaymentProfile')) {
                    if (!$this->cardNumberValidation()) {
                        $validate['status'] = false;
                        $validate['msg'] = 'Invalid credit card number';
                    }
                    if (!$this->cardNameValidation()) {
                        $validate['status'] = false;
                        $validate['msg'] = 'Please fill your cc name';
                    }
                    if (!$this->cardExpiryDateValidation()) {
                        $validate['status'] = false;
                        $validate['msg'] = 'Please check your cc expiry month and year';
                    }
                    if (!$this->cardBillingAddressValidation()) {
                        $validate['status'] = false;
                        $validate['msg'] = 'Please fill your billing address';
                    }
                }
            }
            if ($this->data->get('giftOption')) {
                $giftOption = true;
                $codId = $this->payments->each(function ($item) use (&$giftOption) {
                    if (
                        stripos($item->terms, 'Cash (C.O.D)') !== false &&
                        $item->id == $this->data->get('paymentType')
                    ) {
                        $giftOption = false;

                        return false;
                    }
                });

                if (!$giftOption) {
                    $validate['status'] = false;
                    $validate['msg'] = 'Gift option is not applicable for COD';
                }
            }
        }

        return $validate;
    }

    public function store()
    {
        $this->updateShipping();
        $this->createBilling();
        $this->cart = $this->cart->fresh();
        $this->cart->payment_id = $this->data->get('paymentType');
        $this->cart->gift_card_rewards = 0;
        if ($this->data->get('rewardSelected')) {
            $this->cart->gift_card_rewards = 1;
        }
        $this->cart->company_payment_access_number = $this->data->get('poNumber');
        $ccId = '';
        $this->payments->map(function ($payment) use (&$ccId) {
            if ($payment->terms == 'Credit Card - Payment On Delivery') {
                $ccId = $payment->id;
            }
        });
        if ($this->cart->user_cc_id && $this->data->get('paymentType') != $ccId) {
            $this->cart->cim_payment_profile_id = null;
            // $this->cart->user_cc_id = null;
        }

        if (is_null($this->data->get('selectedTip')) && $this->data->get('tipAmount') >= 0) {
            $this->cart->gratuity_percentage = null;
            $this->cart->gratuity = $this->data->get('tipAmount');
        } elseif (is_null($this->data->get('tipAmount')) && $this->data->get('selectedTip') >= 0) {
            if ($this->data->get('selectedTip') == 0) {
                $this->cart->gratuity_percentage = 0;
                $this->cart->gratuity = null;
            } else {
                $total =
                    $this->cart->taxable + $this->cart->nontaxable + $this->cart->delivery_fee + $this->cart->sales_tax;
                $gratuityAmount = round(($total * $this->data->get('selectedTip')) / 100, 2);
                $this->cart->gratuity_percentage = $this->data->get('selectedTip');
                $this->cart->gratuity = (float) $gratuityAmount;
            }
        }
        $this->cart->total =
            $this->cart->taxable +
            $this->cart->nontaxable +
            $this->cart->delivery_fee +
            $this->cart->sales_tax +
            $this->cart->gratuity;
        $this->cart->save();
        if ($this->cart->gift_card_rewards == 1) {
            $this->storeRewardInfo($this->cart);
            if ($this->cart->user->myconfig) {
                $this->cart->user->myconfig->alonti_rewards = 1;
                $this->cart->user->myconfig->update();
            }
        } else {
            $reward = Reward::where(['cart_id' => $this->cart->id])->first();
            if ($reward) {
                $reward->delete();
            }
        }
        $this->cart->calculateAndUpdate();

        return $this->cart;
    }

    public function processPaymentBu()
    {
        Log::info('StorePayment: started processing payment');
        if (request('recentPaymentProfile') && request('existPaymentProfile')) {
            $this->cart->cim_payment_profile_id = request('existPaymentProfile');
            $this->cart->save();
        } elseif (request('isCardSelected')) {
            $paymentDetails = request('paymentCardDetails');
            $state = State::find($this->getState());
            $paymentDetails['billing']['state'] = $state->name;
            $paymentInfo['paymentDetails'] = $paymentDetails;
            $paymentInfo['isNewProfile'] = request('isCardSelected');
            $activeCart = app(CartManager::class)->getActiveCart();
            $user = $activeCart->user;

            $service = new PaymentService($user, $activeCart, $paymentInfo);
            $service->createAnetProfile();
        }
    }

    private function updateShipping()
    {
        if ($this->data->get('giftOption')) {
            $shipping = [
                'delivery_as_gift' => 1,
            ];
            $this->cart->shipping->update($shipping);
        }

        return true;
    }

    private function createBilling()
    {
        $billing = [
            'cart_id' => $this->cart->id,
            'first_name' => $this->getFname(),
            'last_name' => $this->getLname(),
            'email' => $this->getEmail(),
            'phone_number' => $this->getPhoneNumber(),
            'secondary_phone_number' => $this->getSecondaryPhoneNumber(),
            'address1' => $this->getAddrOne(),
            'address2' => $this->getAddrTwo(),
            'city' => $this->getCity(),
            'state' => $this->getState(),
            'zipcode' => $this->getZipcode(),
        ];
        if (!$this->cart->billing) {
            Billing::create($billing);
        } else {
            $this->cart->billing->update($billing);
        }

        return true;
    }

    private function getFname()
    {
        return $this->cart->shipping->first_name;
    }

    private function getLname()
    {
        return $this->cart->shipping->last_name;
    }

    private function getEmail()
    {
        return $this->cart->shipping->email;
    }

    private function getPhoneNumber()
    {
        return $this->cart->shipping->phone_number;
    }

    private function getSecondaryPhoneNumber()
    {
        return $this->cart->shipping->secondary_phone_number;
    }

    private function getAddrOne()
    {
        if ($this->data->get('isCardSelected')) {
            $address = $this->data->get('paymentCardDetails')['billing']['address'];
        } else {
            $address = $this->cart->shipping->address1;
        }

        return $address;
    }

    private function getAddrTwo()
    {
        if ($this->data->get('isCardSelected')) {
            $address2 = $this->data->get('paymentCardDetails')['billing']['address_two'];
        } else {
            $address2 = $this->cart->shipping->address2;
        }

        return $address2;
    }

    private function getCity()
    {
        if ($this->data->get('isCardSelected')) {
            $city = $this->data->get('paymentCardDetails')['billing']['city'];
        } else {
            $city = $this->cart->shipping->city;
        }

        return $city;
    }

    private function getState()
    {
        if ($this->data->get('isCardSelected')) {
            $state = $this->data->get('paymentCardDetails')['billing']['stateSelected'];
        } else {
            $state = $this->cart->shipping->state;
        }

        return $state;
    }

    private function getZipcode()
    {
        if ($this->data->get('isCardSelected')) {
            $zipcode = $this->data->get('paymentCardDetails')['billing']['zipcode'];
        } else {
            $zipcode = $this->cart->shipping->zipcode;
        }

        return $zipcode;
    }

    private function getCardDetails()
    {
        return $this->data->get('paymentCardDetails')['cardDetails'];
    }

    private function getCardBillingDetails()
    {
        return $this->data->get('paymentCardDetails')['billing'];
    }

    private function cardNumberValidation()
    {
        $cardInfo = $this->getCardDetails();
        $ccNum = str_replace(' ', '', $cardInfo['cardNumber']);
        if ($ccNum == '') {
            return false;
        }
        $lenCCNum = strlen($ccNum);
        if ($lenCCNum < 11 || $lenCCNum > 19) {
            return false;
        }

        return true;
    }

    private function cardNameValidation()
    {
        $cardInfo = $this->getCardDetails();
        if ($cardInfo['cardName'] == '') {
            return false;
        }

        return true;
    }

    private function cardExpiryDateValidation()
    {
        $cardInfo = $this->getCardDetails();
        if ($cardInfo['monthSelected'] == '' || $cardInfo['yearSelected'] == '') {
            return false;
        }

        // $now = time();
        // $expirtDate = $cardInfo['monthSelected']."-".$cardInfo['yearSelected'];
        // $format = strtotime(Carbon::createFromFormat('m-Y', $expirtDate)->format('Y-m'));
        // if($now > $expirtDate){
        // 	return false;
        // }
        return true;
    }

    private function cardBillingAddressValidation()
    {
        $billing = $this->getCardBillingDetails();
        if (
            $billing['address'] == '' ||
            $billing['city'] == '' ||
            $billing['zipcode'] == '' ||
            $billing['stateSelected'] == ''
        ) {
            return false;
        }

        return true;
    }

    public function storeRewardInfo($cart)
    {
        $rewardExist = app(Reward::class)->cartRewardValue($cart->id);
        $rewardConfigVal = Configuration::where([
            'column_key' => 'reward_type',
        ])->first();
        $rewardValue = 0;
        if ($rewardConfigVal->column_value == 'percentage') {
            $rewardValue = round((($cart->taxable + $cart->nontaxable) * $rewardConfigVal->field_value) / 100, 2);
        } else {
            $rewardValue = $rewardConfigVal->field_value;
        }
        $data = [
            'user_id' => $cart->user_id,
            'cart_id' => $cart->id,
            'order_id' => $cart->order_id,
            'cafe_id' => $cart->cafe_id ? $cart->cafe_id : null,
            'reward_value' => $rewardValue,
        ];
        if ($rewardExist) {
            $rewardExist->update($data);
        } else {
            Reward::create($data);
        }
    }

    public function processPayment()
    {
        if (request('recentPaymentProfile') && request('existPaymentProfile')) {
            $this->cart->cim_payment_profile_id = request('existPaymentProfile');
            $this->cart->save();
        } else {
            $paytrace = new PaytraceService();
            $generateTokenRes = $paytrace->connetPaytrace();
            $res = json_decode($generateTokenRes->getBody(true)->getContents());
            $activeCart = app(CartManager::class)->getActiveCart();
            $cardData = $this->prepareCardData($activeCart->user);
            $userCardPaymentProfileId = $cardData['customer_id'];
            $token = $res->token_type . ' ' . $res->access_token;
            $cardType = getCardBrand($cardData['credit_card']['number'], false);
            $createCardResponse = $paytrace->createCard($token, $cardData);
            $ccRes = json_decode($createCardResponse->getBody(true)->getContents());

            $this->createCustomerCardRecord(
                $activeCart->user->id,
                $ccRes->customer_id,
                $ccRes->masked_card_number,
                $this->data->get('paymentCardDetails')['cardDetails']['cardName'],
                $cardType
            );
        }
    }

    public function createCustomerCardRecord($userId, $userCardPaymentProfileId, $maskCCNum, $cardHolderName, $cardType)
    {
        $cim = Cim::where('alonti_user_id', $userId)->first();
        if (!$cim) {
            $profile_id = rand(1111111111, 9999999999); // This is not required for paytrace
            $shipping_id = rand(2222222222, 8888888888); // This is not required for paytrace
            $cimResult = Cim::create([
                'email' => $this->cart->user->email,
                'profile_id' => $profile_id,
                'shipping_id' => $shipping_id,
                'alonti_user_id' => $userId,
            ]);
            $cimId = $cimResult->id;
        } else {
            $profile_id = $cim->profile_id;
            $cimId = $cim->id;
        }
        $result = CimPaymentProfile::create([
            'cim_id' => $cimId,
            'profile_id' => $profile_id,
            'payment_profile_id' => $userCardPaymentProfileId,
            'card_number' => str_replace('x', '', $maskCCNum),
            'last_name' => $cardHolderName,
            'is_display' => 1,
            'gateway_name' => 'PAYTRACE',
            'card_type' => $cardType,
        ]);

        if ($result) {
            $this->storeBillingAddress($result, $cardHolderName);
            $this->cart->cim_payment_profile_id = $result->id;
            $this->cart->save();
        }

        return $result;
    }

    public function storeBillingAddress($result, $cardHolderName)
    {
        $billingDetails = $this->data->get('paymentCardDetails')['billing'];
        $state = State::find($this->getState());
        $data = [
            'user_cc_id' => $result->id,
            'name' => $cardHolderName,
            'address' => !empty($billingDetails['address_two'])
                ? $billingDetails['address'] . ', ' . $billingDetails['address_two']
                : $billingDetails['address'],
            'city' => $billingDetails['city'],
            'state' => $state ? $state->code : '',
            'zipcode' => $billingDetails['zipcode'],
        ];
        $result = UserCreditCardAddress::create($data);

        return $result;
    }

    public function prepareCardData($user)
    {
        $rand = rand(1111111111, 9999999999);
        $uniqueProfileId = md5($user->id . $rand);
        $cardDetails = $this->data->get('paymentCardDetails')['cardDetails'];
        $billingDetails = $this->data->get('paymentCardDetails')['billing'];
        $state = State::find($this->getState());

        $addr = !empty($billingDetails['address_two'])
            ? $billingDetails['address'] . ', ' . $billingDetails['address_two']
            : $billingDetails['address'];
        $addr = str_ireplace($billingDetails['city'], '', $addr);
        $addr = str_ireplace($state->code, '', $addr);
        $addr = str_ireplace($state->name, '', $addr);
        $addrSplit = preg_split('/[\s,]+/', $addr);
        $filterdAddr = array_unique(array_filter($addrSplit));
        $billingAddress = implode(',', $filterdAddr);
        $data = [
            'customer_id' => strtoupper($uniqueProfileId),
            'credit_card' => [
                'number' => $cardDetails['cardNumber'],
                'expiration_month' => $cardDetails['monthSelected'],
                'expiration_year' => $cardDetails['yearSelected'],
            ],
            'integrator_id' => config('payment.drivers.Paytrace.integrator_id'),
            'billing_address' => [
                'name' => $cardDetails['cardName'],
                'street_address' => !empty($billingDetails['address_two'])
                    ? $billingDetails['address'] . ', ' . $billingDetails['address_two']
                    : $billingDetails['address'],
                'city' => $billingDetails['city'],
                'state' => $state->code,
                'zip' => $billingDetails['zipcode'],
            ],
        ];

        return $data;
    }

    public function storeApiErrorLog($errorMsg)
    {
        $result = PaytraceApiLog::create([
            'user_id' => $this->cart->user->id,
            'cart_id' => $this->cart->id,
            'api_error' => $errorMsg,
        ]);

        return $result;
    }
}
