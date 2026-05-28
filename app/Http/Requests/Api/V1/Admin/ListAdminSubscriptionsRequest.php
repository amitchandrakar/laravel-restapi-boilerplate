<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;

class ListAdminSubscriptionsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.subscriptions.view') ?? false;
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
            'package_id' => ['sometimes', 'integer', 'exists:packages,id'],
            'ends_from' => ['sometimes', 'date'],
            'ends_to' => ['sometimes', 'date', 'after_or_equal:ends_from'],
            'sort' => ['sometimes', 'string', 'in:latest,oldest,candidate,package,starts,ends,status'],
            'sort_dir' => ['sometimes', 'string', 'in:asc,desc'],
        ];
    }
}
