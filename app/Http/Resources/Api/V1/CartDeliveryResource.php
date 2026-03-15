<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartDeliveryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = is_array($this->resource) ? $this->resource : [];

        return [
            'cart_info' => $data['cart_info'] ?? null,
            'past_delivery_address' => $data['past_delivery_address'] ?? null,
            'delivery_times' => $data['delivery_times'] ?? [],
            'industries' => $data['industries'] ?? [],
            'states' => $data['states'] ?? [],
            'given_zip_code' => $data['given_zip_code'] ?? null,
            'pickup_zipcode' => $data['pickup_zipcode'] ?? null,
            'pickup_cafes' => $data['pickup_cafes'] ?? null,
            'display_wc_personalised_msg' => $data['display_wc_personalised_msg'] ?? null,
            'existing_delivery_address_count' => $data['existing_delivery_address_count'] ?? null,
            'disable_dates' => $data['disable_dates'] ?? null,
            'allow_weekend_orders' => $data['allow_weekend_orders'] ?? null,
        ];
    }
}
