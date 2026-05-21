<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use App\Services\PackageService;
use Illuminate\Validation\Rule;

class ListPackagesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.packages.view') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }

        if ($this->has('is_popular')) {
            $this->merge(['is_popular' => $this->boolean('is_popular')]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'duration_unit' => ['sometimes', 'string', Rule::in(['month', 'year'])],
            'is_popular' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'string', Rule::in(PackageService::SORT_OPTIONS)],
        ];
    }
}
