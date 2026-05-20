<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;

class ReviewKycDocumentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof \App\Models\UserVerificationDocument
            && ($this->user()?->can('review', $document) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'verification_status' => ['required', 'string', 'in:approved,rejected,resubmission_required'],
            'rejection_reason' => ['required_if:verification_status,rejected', 'nullable', 'string', 'max:5000'],
        ];
    }
}
