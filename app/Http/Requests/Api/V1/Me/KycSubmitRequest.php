<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Me;

use App\Http\Requests\Api\ApiFormRequest;

class KycSubmitRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'string', 'uuid'],
            'document_number_masked' => ['nullable', 'string', 'max:255'],
        ];
    }
}
