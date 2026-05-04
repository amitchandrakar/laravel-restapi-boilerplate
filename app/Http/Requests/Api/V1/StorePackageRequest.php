<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StorePackageRequest extends ApiFormRequest
{
    /**
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('packages', 'name')->where(static function ($query) {
                    return $query->where('is_active', true)->whereNull('deleted_at');
                }),
            ],
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('packages', 'code')->where(static function ($query) {
                    return $query->where('is_active', true)->whereNull('deleted_at');
                }),
            ],
            'description' => ['nullable', 'string'],
            'duration_unit' => ['required', 'string', Rule::in(['month', 'year'])],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'yearly_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['nullable', 'boolean'],
            'is_default_registration' => ['nullable', 'boolean'],
            'is_popular' => ['nullable', 'boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where(static function ($query) {
                    return $query->where('name', 'like', 'candidate.%');
                }),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
