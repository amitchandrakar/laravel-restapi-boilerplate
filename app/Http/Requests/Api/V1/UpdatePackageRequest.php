<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Package;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends ApiFormRequest
{
    /**
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        /** @var Package|null $package */
        $package = $this->route('package');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('packages', 'name')
                    ->ignore($package?->id)
                    ->where(static function ($query) {
                        return $query->where('is_active', true)->whereNull('deleted_at');
                    }),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                Rule::unique('packages', 'code')
                    ->ignore($package?->id)
                    ->where(static function ($query) {
                        return $query->where('is_active', true)->whereNull('deleted_at');
                    }),
            ],
            'description' => ['nullable', 'string'],
            'duration_unit' => ['sometimes', 'required', 'string', Rule::in(['month', 'year'])],
            'monthly_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'yearly_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default_registration' => ['sometimes', 'boolean'],
            'is_popular' => ['sometimes', 'boolean'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where(static function ($query) {
                    return $query->where('name', 'like', 'candidate.%');
                }),
            ],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
