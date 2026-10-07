<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Registration payments
    |--------------------------------------------------------------------------
    |
    | When false, paid packages still show catalog prices; checkout requires
    | payment gateway or a coupon that reduces the price to zero.
    |
    */
    'registration_payments_enabled' => (bool) env('REGISTRATION_PAYMENTS_ENABLED', true),
];
