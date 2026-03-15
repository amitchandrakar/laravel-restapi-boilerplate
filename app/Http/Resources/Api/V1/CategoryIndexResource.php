<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryIndexResource extends JsonResource
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
            'categories' => $data['categories'] ?? [],
            'lunch_category' => $data['lunch_category'] ?? null,
            'individual_cart' => $data['individual_cart'] ?? null,
            'cart_info' => $data['cart_info'] ?? null,
            'individual_dietary_id' => $data['individual_dietary_id'] ?? 0,
            'from_date' => $data['from_date'] ?? null,
            'current_date' => $data['current_date'] ?? null,
            'help' => $data['help'] ?? false,
            'banner_setting' => $data['banner_setting'] ?? null,
        ];
    }
}
