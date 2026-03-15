<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderUpdatedReceiptResource extends JsonResource
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
            'allow_refer' => $data['allow_refer'] ?? false,
        ];
    }
}
