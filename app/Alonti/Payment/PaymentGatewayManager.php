<?php

declare(strict_types=1);


namespace App\Alonti\Payment;

use App\Alonti\Payment\Drivers\AuthorizenetDriver;
use Illuminate\Support\Manager;

class PaymentGatewayManager extends Manager
{
    /**
     * Get a driver instance.
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function channel($name = null)
    {
        return $this->driver($name);
    }

    public function createAuthorizenetDriver()
    {
        return new AuthorizenetDriver(config('payment.drivers.authorizenet'));
    }

    public function getDefaultDriver()
    {
        return $this->app['config']['payment.default'] ?? 'null';
    }
}
