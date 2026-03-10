<?php

declare(strict_types=1);

namespace App\Alonti\Payment\Paytrace;

use App\Traits\PaytraceTrait;
use Illuminate\Support\Facades\DB;

class PaytraceManager
{
    use PaytraceTrait;

    public $username;

    public $pass;

    public $api_url;

    public $mode;

    public $grant_type;

    public $client;

    public function __construct()
    {
        $this->loadPaytraceConfig();
    }

    public function connectToPaytrace()
    {
        $oauth_response = $this->generateToken();

        return $oauth_response;
    }

    public function generateToken()
    {
        // Get paytrace password from the database
        $settings = DB::table('settings')->first();

        if ($settings && $settings->paytrace_password) {
            // Decrypt the password
            $password = encryptDecryptPassword($settings->paytrace_password, 'decrypt');
            $this->pass = $password;
        } else {
            $this->pass = config('payment.drivers.Paytrace.password');
        }

        $authUrl = $this->api_url . config('payment.drivers.Paytrace.URL_OAUTH');
        $response = $this->client->post($authUrl, [
            'form_params' => [
                'username' => $this->username,
                'password' => $this->pass,
                'grant_type' => $this->grant_type,
            ],
        ]);

        return $response;
    }

    public function processTheRequest($url, $token, $requestData)
    {
        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $token,
            ],
            'body' => $requestData,
        ]);

        return $response;
    }
}
