<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Support\UserImageStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPendingKycDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var UserVerificationDocument $doc */
        $doc = $this->resource;
        $candidate = $doc->user;

        return [
            'uuid' => $doc->uuid,
            'documentType' => $doc->document_type,
            'documentNumberMasked' => $doc->document_number_masked,
            'documentFrontUrl' => UserImageStorageUrl::resolveKycDocumentUrl($doc->document_front_url),
            'documentBackUrl' => UserImageStorageUrl::resolveKycDocumentUrl($doc->document_back_url),
            'selfieUrl' => UserImageStorageUrl::resolveKycDocumentUrl($doc->selfie_url),
            'verificationStatus' => $doc->verification_status,
            'submittedAt' => optional($doc->submitted_at)?->toIso8601String(),
            'candidate' => $candidate instanceof User
                    ? [
                        'uuid' => $candidate->uuid,
                        'firstName' => $candidate->first_name,
                        'lastName' => $candidate->last_name,
                        'email' => $candidate->email,
                    ]
                    : null,
        ];
    }
}
