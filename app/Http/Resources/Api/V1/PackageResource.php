<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Package $package */
        $package = $this->resource;
        $durationUnit = (string) ($package->duration_unit ?? 'year');
        $durationDays = $durationUnit === 'year' ? 365 : 30;
        $monthlyPrice = (float) ($package->monthly_price ?? 0);
        $yearlyPrice = (float) ($package->yearly_price ?? ($package->price ?? 0));
        $displayPrice = $durationUnit === 'year' ? $yearlyPrice : $monthlyPrice;
        $pricePerDay = round($displayPrice / $durationDays, 2);
        $featurePermissions = $package
            ->permissions()
            ->where('permissions.name', 'like', 'candidate.%')
            ->orderBy('permissions.name')
            ->get(['permissions.id', 'permissions.name', 'permissions.title'])
            ->map(
                /**
                 * @return array{id:int,name:string,title:string}
                 */
                static function ($permission): array {
                    $name = (string) data_get($permission, 'name', '');
                    $title = (string) data_get($permission, 'title', $name);

                    return [
                        'id' => (int) data_get($permission, 'id', 0),
                        'name' => $name,
                        'title' => $title,
                    ];
                }
            )
            ->values()
            ->all();

        return [
            'id' => $package->id,
            'uuid' => $package->uuid,
            'name' => $package->name,
            'code' => $package->code,
            'description' => $package->description,
            'durationUnit' => $durationUnit,
            'durationDays' => $durationDays,
            'pricePerDay' => $pricePerDay,
            'monthlyPrice' => $monthlyPrice,
            'yearlyPrice' => $yearlyPrice,
            'price' => $package->price,
            'discountedPrice' => $package->discounted_price,
            'currency' => $package->currency,
            'isActive' => $package->is_active,
            'isDefaultRegistration' => (bool) ($package->is_default_registration ?? false),
            'isPopular' => (bool) ($package->is_popular ?? false),
            'featurePermissions' => $featurePermissions,
            'sortOrder' => $package->sort_order,
            'createdBy' => $package->created_by,
            'updatedBy' => $package->updated_by,
            'createdAt' => $package->created_at,
            'updatedAt' => $package->updated_at,
        ];
    }
}
