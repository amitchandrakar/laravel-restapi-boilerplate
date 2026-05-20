<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\ContactRequest;
use Illuminate\Validation\Rule;

class RespondContactRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $contactRequest = $this->route('contactRequest');

        return $contactRequest instanceof ContactRequest && ($this->user()?->can('respond', $contactRequest) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', Rule::in(['accepted', 'rejected'])],
            'responseMessage' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
