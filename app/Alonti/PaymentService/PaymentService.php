<?php

declare(strict_types=1);


namespace App\Alonti\PaymentService;

use App\Alonti\Payment\Drivers\AuthorizenetException;
use App\Models\Cart;
use App\Models\User;

class PaymentService
{
    /**
     * Payment gateway driver instance
     *
     * @var mixed
     */
    public $gateway;

    /**
     * Flag indicating if profile was created via gateway
     *
     * @var bool
     */
    public $profileCreatedViaGateway;

    /**
     * User instance for payment processing
     *
     * @var User
     */
    public $user;

    /**
     * Cart instance for payment processing
     *
     * @var Cart
     */
    public $cart;

    /**
     * Payment profile information
     *
     * @var array
     */
    public $profileInfo;

    /**
     * Flag indicating if new payment profile should be created
     *
     * @var bool
     */
    public $shouldCreateNewPaymentProfile;

    public function __construct(User $user, Cart $cart, $profileInfo = [])
    {
        $this->gateway = app('payment')->driver('authorizenet');
        $this->profileCreatedViaGateway = false;
        $this->user = $user;
        $this->cart = $cart;
        $this->profileInfo = $profileInfo;
    }

    public function createAnetProfile()
    {
        $cim = $this->findOrCreateCimRecord();
        $this->shouldCreateNewPaymentProfile = false;
        if ($cim) {
            $this->shouldCreateNewPaymentProfile =
                isset($this->profileInfo['isNewProfile']) && $this->profileInfo['isNewProfile'] ? true : false;
        }
        if ($this->shouldCreateNewPaymentProfile && !$this->profileCreatedViaGateway) {
            // try {
            $this->createPaymentProfileForExistingProfile();
            // } catch(AuthorizenetException $e) {
            // dd($e->getMessage());
            // }
        }
    }

    private function createPaymentProfileForExistingProfile()
    {
        $profileId = $this->user->cim->profile_id;
        $output = $this->gateway
            ->setPaymentInfo($this->profileInfo['paymentDetails']['cardDetails'])
            ->setBillingInfo($this->user, $this->profileInfo['paymentDetails']['billing'])
            ->setShippingInfo($this->user)
            ->processForExistingProfile($profileId);
        $cardNumber = str_replace(' ', '', $this->profileInfo['paymentDetails']['cardDetails']['cardNumber']);
        $cardName = isset($this->profileInfo['paymentDetails']['cardDetails']['cardName'])
            ? $this->profileInfo['paymentDetails']['cardDetails']['cardName']
            : $this->user->lname;
        $lastFourDigits = substr($cardNumber, -4);
        $paymentProfile = $this->user->cim->paymentProfiles()->create([
            'profile_id' => $output['profileId'],
            'payment_profile_id' => $output['paymentProfileId'],
            'last_name' => $cardName,
            'card_number' => $lastFourDigits,
            'is_display' => 1,
        ]);
        $this->cart->cim_payment_profile_id = $paymentProfile->id;
        $this->cart->save();
    }

    public function findOrCreateCimRecord()
    {
        if ($this->user->cim) {
            return $this->user->cim;
        }

        return $this->createProfileViaGateway();
    }

    public function createProfileViaGateway()
    {
        $this->profileCreatedViaGateway = true;
        $response = null;
        $output = $this->gateway
            ->setPaymentInfo($this->profileInfo['paymentDetails']['cardDetails'])
            ->setBillingInfo($this->user, $this->profileInfo['paymentDetails']['billing'])
            ->setShippingInfo($this->user)
            ->setCustomerInfo($this->user)
            ->process();

        $response = $this->createCimRecord($output);

        return $response;
    }

    private function createCimRecord($profileInfo)
    {
        $cardNumber = str_replace(' ', '', $this->profileInfo['paymentDetails']['cardDetails']['cardNumber']);
        $lastFourDigits = substr($cardNumber, -4);
        $this->user->cim()->insert([
            'email' => $this->user->email,
            'profile_id' => $profileInfo['profileId'],
            'shipping_id' => $profileInfo['shippingProfileIds'][0],
            'alonti_user_id' => $this->user->id,
        ]);

        $this->user = $this->user->fresh();
        $cardName = isset($this->profileInfo['paymentDetails']['cardDetails']['cardName'])
            ? $this->profileInfo['paymentDetails']['cardDetails']['cardName']
            : $this->user->lname;
        $paymentProfile = $this->user->cim->paymentProfiles()->create([
            'profile_id' => $profileInfo['profileId'],
            'payment_profile_id' => $profileInfo['paymentProfileIds'][0],
            'last_name' => $cardName,
            'card_number' => $lastFourDigits,
            'is_display' => 1,
        ]);

        $this->cart->cim_payment_profile_id = $paymentProfile->id;
        $this->cart->save();

        return $this->user->cim;
    }

    public function voidTransaction($transactionId)
    {
        $this->gateway->voidTransaction($transactionId);
    }

    public function deletePaymentProfile($authoriseDetail = [])
    {
        $this->gateway->deletePaymentProfile($authoriseDetail);
    }
}
