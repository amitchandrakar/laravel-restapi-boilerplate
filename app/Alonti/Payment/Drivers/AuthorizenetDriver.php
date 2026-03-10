<?php

declare(strict_types=1);

namespace App\Alonti\Payment\Drivers;

use App\Alonti\Payment\Contracts\PaymentGateway;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AuthorizenetDriver implements PaymentGateway
{
    /**
     * Payment gateway configuration
     *
     * @var array
     */
    public $config;

    /**
     * Authorize.net environment (production/sandbox)
     *
     * @var string
     */
    public $environment;

    /**
     * Merchant authentication object
     *
     * @var AnetAPI\MerchantAuthenticationType
     */
    public $merchantAuthentication;

    /**
     * Credit card information object
     *
     * @var AnetAPI\CreditCardType
     */
    public $creditCard;

    /**
     * Billing address object
     *
     * @var AnetAPI\CustomerAddressType
     */
    public $billTo;

    /**
     * Shipping address object
     *
     * @var AnetAPI\CustomerAddressType
     */
    public $shipping;

    /**
     * Customer profile object
     *
     * @var AnetAPI\CustomerProfileType
     */
    public $profile;

    public function __construct($config)
    {
        $this->config = $config;
        $this->environment =
            $config['env'] === 'production'
                ? \net\authorize\api\constants\ANetEnvironment::PRODUCTION
                : \net\authorize\api\constants\ANetEnvironment::SANDBOX;
        $this->merchantAuthentication = null;
        $this->setupGetway();

        /**
         * @creditCard AnetAPI\CreditCardType
         */
        $this->creditCard = null;

        /**
         * @billTo AnetAPI\CustomerAddressType
         */
        $this->billTo = null;

        /**
         * @shipping AnetAPI\CustomerAddressType
         */
        $this->shipping = null;

        /**
         * @profile AnetAPI\CustomerProfileType
         */
        $this->profile = null;
    }

    private function setupGetway()
    {
        $this->merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $this->merchantAuthentication->setName($this->config['login_id']);
        $this->merchantAuthentication->setTransactionKey($this->config['transaction_key']);
    }

    private function linkBillingWithPayment()
    {
        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
        $paymentProfile->setCustomerType('individual');
        $paymentProfile->setBillTo($this->billTo);
        $paymentProfile->setPayment($this->creditCard);

        return $paymentProfile;
    }

    public function setCustomerInfo($user)
    {
        $customerProfile = new AnetAPI\CustomerProfileType();
        $customerProfile->setDescription('Alonti FrontEnd Add Card');
        $customerProfile->setMerchantCustomerId('M_' . time());
        $customerProfile->setEmail($user->email);

        $paymentProfile = $this->linkBillingWithPayment();
        $customerProfile->setpaymentProfiles([$paymentProfile]);

        $customerProfile->setShipToList([$this->shipping]);

        $this->profile = $customerProfile;

        return $this;
    }

    public function processForExistingProfile($existingcustomerprofileid)
    {
        // Set credit card information for payment profile
        $creditCard = $this->creditCard;
        // Create the Bill To info for new payment type
        $billto = $this->billTo;
        // Create a new Customer Payment Profile object
        $paymentprofile = $this->linkBillingWithPayment();

        // Assemble the complete transaction request
        $paymentprofilerequest = new AnetAPI\CreateCustomerPaymentProfileRequest();
        $paymentprofilerequest->setMerchantAuthentication($this->merchantAuthentication);
        // Add an existing profile id to the request
        $paymentprofilerequest->setCustomerProfileId($existingcustomerprofileid);
        $paymentprofilerequest->setPaymentProfile($paymentprofile);
        // $paymentprofilerequest->setValidationMode("liveMode");
        // Create the controller and get the response
        $controller = new AnetController\CreateCustomerPaymentProfileController($paymentprofilerequest);
        $response = $controller->executeWithApiResponse($this->environment);
        $output = [];
        if ($response != null && $response->getMessages()->getResultCode() == 'Ok') {
            // echo "Create Customer Payment Profile SUCCESS: " . $response->getCustomerPaymentProfileId() . "\n";
            $output['status'] = true;
            $output['profileId'] = $response->getCustomerProfileId();
            $output['paymentProfileId'] = $response->getCustomerPaymentProfileId();
            $output['msg'] = '';

            return $output;
        } else {
            $errorMessages = $response->getMessages()->getMessage();
            // $errorMessages = "Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
            $errorMessages = $errorMessages[0]->getText();
            throw new AuthorizenetException($errorMessages);
        }
    }

    public function process()
    {
        return $this->createCustomerProfileRequest($this->profile);
    }

    public function setShippingInfo($user)
    {
        $shipping = new AnetAPI\CustomerAddressType();
        $shipping->setFirstName($user->fname);
        $shipping->setLastName($user->lname);
        $shipping->setCompany($user->company);
        $shipping->setAddress($user->addr);
        $shipping->setCity($user->city);
        $shipping->setState($user->state);
        $shipping->setZip($user->zip);
        $shipping->setCountry('USA');
        $shipping->setPhoneNumber($user->phone);
        $shipping->setfaxNumber($user->fax);

        $this->shipping = $shipping;

        return $this;
    }

    public function setBillingInfo($user, $cardBillingAddress)
    {
        $billTo = new AnetAPI\CustomerAddressType();
        $billTo->setFirstName($user->fname);
        $billTo->setLastName($user->lname);
        $billTo->setCompany($user->company);
        $billTo->setAddress($cardBillingAddress['address']);
        $billTo->setCity($cardBillingAddress['city']);
        $billTo->setState($cardBillingAddress['state']);
        $billTo->setZip($cardBillingAddress['zipcode']);
        $billTo->setCountry('USA');
        $billTo->setPhoneNumber($user->phone);
        $billTo->setfaxNumber($user->fax);
        $this->billTo = $billTo;

        return $this;
    }

    public function setPaymentInfo($details)
    {
        $creditCard = new AnetAPI\CreditCardType();
        $cardNumber = str_replace(' ', '', $details['cardNumber']);
        $creditCard->setCardNumber($cardNumber);

        $month = (string) $details['monthSelected'];
        $monthTwoDigits = $month < 10 ? '0' . $month : $month;

        $creditCard->setExpirationDate($details['yearSelected'] . '-' . $monthTwoDigits);
        $creditCard->setCardCode('123');
        $paymentCreditCard = new AnetAPI\PaymentType();
        $paymentCreditCard->setCreditCard($creditCard);

        $this->creditCard = $paymentCreditCard;

        return $this;
    }

    public function createCustomerProfileRequest($customerProfile)
    {
        $refId = 'ref' . time();
        // Assemble the complete transaction request
        $request = new AnetAPI\CreateCustomerProfileRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId($refId);

        $request->setProfile($customerProfile);
        // Create the controller and get the response
        $controller = new AnetController\CreateCustomerProfileController($request);
        $response = $controller->executeWithApiResponse($this->environment);

        if ($response != null && $response->getMessages()->getResultCode() == 'Ok') {
            $paymentProfiles = $response->getCustomerPaymentProfileIdList();
        } else {
            $errorMessages = $response->getMessages()->getMessage();
            $errorMessages = $errorMessages[0]->getText();
            throw new AuthorizenetException($errorMessages);
        }
        $output = [];

        $output['profileId'] = $response->getCustomerProfileId();
        $output['paymentProfileIds'] = $response->getCustomerPaymentProfileIdList();
        $output['shippingProfileIds'] = $response->getCustomerShippingAddressIdList();
        $output['refId'] = $response->getRefId();

        return $output;
    }

    public function getProfileById($profileId)
    {
        $profileResponse = null;
        $request = new AnetAPI\GetCustomerProfileRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setCustomerProfileId($profileId);
        $controller = new AnetController\GetCustomerProfileController($request);
        $response = $controller->executeWithApiResponse($this->environment);

        if ($response != null && $response->getMessages()->getResultCode() == 'Ok') {
            echo 'GetCustomerProfile SUCCESS : ' . "\n";
            $profileSelected = $response->getProfile();
            // dd($profileSelected->getShipToList());

            $shippingProfiles = collect($profileSelected->getShipToList())->map(function ($shipping) {
                $shippingId = $shipping->getCustomerAddressId();

                return compact('shippingId');
            });

            $paymentProfiles = collect($profileSelected->getPaymentProfiles())->map(function ($paymentProfile) {
                $profileId = $paymentProfile->getCustomerPaymentProfileId();
                $maskedCard = $paymentProfile->getPayment()->getCreditCard();
                $cardNumber = $maskedCard->getCardNumber();
                $cardType = $maskedCard->getCardType();

                // dd(get_class_methods($paymentProfile->getPayment()->getCreditCard()));
                return compact('profileId', 'cardNumber', 'cardType');
            });

            $profileResponse = [
                'originalProfile' => $profileSelected,
                'profileId' => $profileSelected->getCustomerProfileId(),
                'email' => $profileSelected->getEmail(),
                'paymentProfiles' => $paymentProfiles,
                'shippingProfiles' => $shippingProfiles,
            ];
        } else {
            // echo "ERROR :  GetCustomerProfile: Invalid response\n";
            $errorMessages = $response->getMessages()->getMessage();
            // echo "Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
            $errorMessages = $errorMessages[0]->getText();
            throw new AuthorizenetException($errorMessages);
        }

        return $profileResponse;
    }

    public function voidTransaction($transactionId)
    {
        $refId = 'ref' . time();
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType('voidTransaction');
        $transactionRequestType->setRefTransId($transactionId);
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse($this->environment);
        $output = [];
        if ($response != null && $response->getMessages()->getResultCode() == 'Ok') {
            $output['status'] = true;

            return $output;
        } else {
            $errorMessages = $response->getMessages()->getMessage();
            // $errorMessages = "Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
            $errorMessages = $errorMessages[0]->getText();
            throw new AuthorizenetException($errorMessages);
        }
    }

    public function deletePaymentProfile($authorizeDetail = [])
    {
        $request = new AnetAPI\DeleteCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setCustomerProfileId($authorizeDetail['customer_profile_id']);
        $request->setCustomerPaymentProfileId($authorizeDetail['customer_payment_profile_id']);
        $controller = new AnetController\DeleteCustomerPaymentProfileController($request);
        $response = $controller->executeWithApiResponse($this->environment);
        $output = [];
        if ($response != null && $response->getMessages()->getResultCode() == 'Ok') {
            $output['status'] = true;

            return $output;
        } else {
            $errorMessages = $response->getMessages()->getMessage();
            $errorMessages = $errorMessages[0]->getText();
            throw new AuthorizenetException($errorMessages);
        }
    }
}
