<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;

class ListCandidateKycDocumentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.candidates.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
