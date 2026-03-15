<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReceiptResource extends JsonResource
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
            'id' => $data['id'] ?? null,
            'user' => $data['user'] ?? null,
            'spl_occassion_category' => $data['spl_occassion_category'] ?? null,
            'tinga_chicken_power_bowl' => $data['tinga_chicken_power_bowl'] ?? null,
            'soup_category' => $data['soup_category'] ?? null,
            'order' => $data['order'] ?? null,
            'items' => $data['items'] ?? [],
            'payments' => $data['payments'] ?? [],
            'delivery_times' => $data['delivery_times'] ?? [],
            'delivery_area_count' => $data['delivery_area_count'] ?? 0,
            'delivery_area_chosen' => $data['delivery_area_chosen'] ?? false,
            'cafe_list' => $data['cafe_list'] ?? [],
            'serving_option' => $data['serving_option'] ?? null,
        ];
    }
}
