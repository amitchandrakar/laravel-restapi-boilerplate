<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class StoreContactRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'candidateUuid' => ['required', 'uuid'],
            'requestMessage' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
