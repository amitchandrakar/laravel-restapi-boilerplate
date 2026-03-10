<?php

declare(strict_types=1);

namespace App\Traits;

use GuzzleHttp\Client;

trait PaytraceTrait
{
    /**
     * Load Paytrace configuration from config/payment and set up HTTP client.
     */
    protected function loadPaytraceConfig(): void
    {
        $driver = config('payment.drivers.Paytrace', []);
        $this->username = $driver['username'] ?? '';
        $this->api_url = ($driver['BASE_URL'] ?? '') . ($driver['API_VERSION'] ?? '');
        $this->mode = $driver['mode'] ?? 'live';
        $this->grant_type = $driver['grant_type'] ?? 'password';
        $this->client = new Client([
            'base_uri' => $this->api_url,
            'timeout' => $driver['timeout'] ?? 30,
        ]);
    }
}
