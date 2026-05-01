<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\UserVerificationDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KycDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var UserVerificationDocument $doc */
        $doc = $this->resource;

        return [
            'uuid' => $doc->uuid,
            'documentType' => $doc->document_type,
            'documentNumberMasked' => $doc->document_number_masked,
            'documentFrontUrl' => $doc->document_front_url,
            'documentBackUrl' => $doc->document_back_url,
            'selfieUrl' => $doc->selfie_url,
            'verificationStatus' => $doc->verification_status,
            'rejectionReason' => $doc->rejection_reason,
            'submittedAt' => optional($doc->submitted_at)?->toIso8601String(),
            'verifiedAt' => optional($doc->verified_at)?->toIso8601String(),
        ];
    }
}
