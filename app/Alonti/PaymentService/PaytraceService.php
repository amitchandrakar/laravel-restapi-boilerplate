<?php

declare(strict_types=1);

namespace App\Alonti\PaymentService;

use App\Alonti\Payment\Paytrace\PaytraceManager;

class PaytraceService
{
    public $paytrace;

    public function __construct()
    {
        $this->paytrace = new PaytraceManager();
    }

    public function connetPaytrace()
    {
        $authTokenResponse = $this->paytrace->connectToPaytrace();

        return $authTokenResponse;
    }

    public function createCard($token, $requestData)
    {
        $createCustomerUrl =
            config('payment.drivers.Paytrace.BASE_URL') .
            config('payment.drivers.Paytrace.API_VERSION') .
            config('payment.drivers.Paytrace.URL_CREATE_CUSTOMER');
        $createCustomerResponse = $this->paytrace->processTheRequest(
            $createCustomerUrl,
            $token,
            json_encode($requestData)
        );

        return $createCustomerResponse;
    }

    public function authoriseTransaction($token, $data)
    {
        $authUrl =
            config('payment.drivers.Paytrace.BASE_URL') .
            config('payment.drivers.Paytrace.API_VERSION') .
            config('payment.drivers.Paytrace.URL_KEYED_AUTHORIZATION');
        $authResponse = $this->paytrace->processTheRequest($authUrl, $token, json_encode($data));

        return $authResponse;
    }

    public function captureTransaction($token, $data)
    {
        $url =
            config('payment.drivers.Paytrace.BASE_URL') .
            config('payment.drivers.Paytrace.API_VERSION') .
            config('payment.drivers.Paytrace.URL_CAPTURE');
        $captureResponse = $this->paytrace->processTheRequest($url, $token, json_encode($data));

        return $captureResponse;
    }

    public function voidTransaction($token, $data)
    {
        $url =
            config('payment.drivers.Paytrace.BASE_URL') .
            config('payment.drivers.Paytrace.API_VERSION') .
            config('payment.drivers.Paytrace.URL_VOID_TRANSACTION');
        $voidResponse = $this->paytrace->processTheRequest($url, $token, json_encode($data));

        return $voidResponse;
    }

    public function refundTransaction($token)
    {
        $url =
            config('payment.drivers.Paytrace.BASE_URL') .
            config('payment.drivers.Paytrace.API_VERSION') .
            config('payment.drivers.Paytrace.URL_KEYED_REFUND');
        $data = [
            'amount' => 80.25,
            'credit_card' => [
                'number' => '4012881888818888',
                'expiration_month' => '11',
                'expiration_year' => '2020',
            ],
            'csc' => '999',
            'billing_address' => [
                'name' => 'Princess Leia ',
                'street_address' => '8320 E. West St.',
                'city' => 'Spokane',
                'state' => 'WA',
                'zip' => '84524',
            ],
        ];
        $refundResponse = $this->paytrace->processTheRequest($url, $token, json_encode($data));
        dd($refundResponse);
    }
}
