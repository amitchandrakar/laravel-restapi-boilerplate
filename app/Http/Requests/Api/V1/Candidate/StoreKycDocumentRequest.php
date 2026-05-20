<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use App\Services\KycDocumentService;

class StoreKycDocumentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('candidate');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $types = implode(',', KycDocumentService::allowedDocumentTypes());

        return [
            'document_type' => ['required', 'string', 'in:' . $types],
            'document_number_masked' => ['nullable', 'string', 'max:255'],
            'document_front_url' => ['required', 'url', 'max:2048'],
            'document_back_url' => ['required', 'url', 'max:2048'],
            'selfie_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
