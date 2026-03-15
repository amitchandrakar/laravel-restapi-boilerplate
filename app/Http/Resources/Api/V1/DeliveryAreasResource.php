<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryAreasResource extends JsonResource
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
            'delivery_area_count' => $data['delivery_area_count'] ?? 0,
            'delivery_area_chosen' => $data['delivery_area_chosen'] ?? false,
            'referred_customers' => $data['referred_customers'] ?? [],
        ];
    }
}
