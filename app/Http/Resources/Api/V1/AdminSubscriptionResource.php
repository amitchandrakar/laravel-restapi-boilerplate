<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!($this->resource instanceof Subscription)) {
            return [];
        }

        $subscription = $this->resource;
        $user = null;
        $package = null;

        if ($subscription->relationLoaded('user') && $subscription->user instanceof User) {
            $user = $subscription->user;
        }

        if ($subscription->relationLoaded('package') && $subscription->package instanceof Package) {
            $package = $subscription->package;
        }

        $durationUnit = (string) ($package?->duration_unit ?? 'year');
        $displayPrice =
            $durationUnit === 'year'
                ? (float) ($package?->yearly_price ?? ($package?->price ?? 0))
                : (float) ($package?->monthly_price ?? ($package?->price ?? 0));

        return [
            'id' => $subscription->id,
            'subscriptionUuid' => $subscription->uuid,
            'subscriptionStatus' => $subscription->subscription_status,
            'startedAt' => $subscription->started_at,
            'endsAt' => $subscription->ends_at,
            'autoRenew' => (bool) $subscription->auto_renew,
            'candidate' => [
                'uuid' => $user?->uuid,
                'fullName' => trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? '')),
                'email' => $user?->email,
                'profilePhoto' => $user?->profile_photo_url,
            ],
            'package' => [
                'id' => $package?->id,
                'name' => $package?->name,
                'code' => $package?->code,
                'price' => $displayPrice,
                'currency' => $package?->currency ?? 'INR',
                'durationUnit' => $durationUnit,
            ],
        ];
    }
}
