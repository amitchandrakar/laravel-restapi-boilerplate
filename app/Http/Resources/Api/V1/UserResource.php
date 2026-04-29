<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'firstName' => $user->fname,
            'lastName' => $user->lname,
            'email' => $user->email,
            'secondaryEmail' => $user->secondary_email,
            'phone' => $user->phone,
            'secondaryPhone' => $user->secondary_phone,
            'company' => $user->company,
            'address' => $user->addr,
            'address2' => $user->addr2,
            'city' => $user->city,
            'state' => $user->state,
            'zip' => $user->zip,
        ];
    }
}
