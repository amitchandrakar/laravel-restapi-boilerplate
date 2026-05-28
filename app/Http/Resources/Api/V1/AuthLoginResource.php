<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthLoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = is_array($this->resource) ? $this->resource : [];
        $user = $data['user'] ?? null;

        if ($user instanceof JsonResource) {
            $user = $user->resolve();
        }

        $out = [
            'user' => $user,
            'userType' => $data['userType'] ?? (is_array($user) ? $user['userType'] ?? null : null),
            'token' => $data['token'] ?? null,
            'token_type' => $data['token_type'] ?? 'Bearer',
            'permissions' => $data['permissions'] ?? [],
        ];

        if (array_key_exists('expires_at', $data)) {
            $out['expires_at'] = $data['expires_at'];
        }

        if (array_key_exists('session_token_hash', $data)) {
            $out['session_token_hash'] = $data['session_token_hash'];
        }

        if (array_key_exists('payment', $data) && is_array($data['payment'])) {
            /** @var array<string, mixed> $p */
            $p = $data['payment'];
            $out['payment'] = [
                'paymentUuid' => $p['paymentUuid'] ?? null,
                'orderId' => $p['orderId'] ?? null,
                'keyId' => $p['keyId'] ?? null,
                'amount' => $p['amount'] ?? null,
                'currency' => $p['currency'] ?? null,
                'packageName' => $p['packageName'] ?? null,
                'checkoutOptions' => $p['checkoutOptions'] ?? null,
            ];
        }

        return $out;
    }
}
