<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartServingOptionsResource extends JsonResource
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
            'serving_options' => $data['serving_options'] ?? [],
            'existing_serving_option' => $data['existing_serving_option'] ?? null,
            'paper_products' => $data['paper_products'] ?? [],
        ];
    }
}
