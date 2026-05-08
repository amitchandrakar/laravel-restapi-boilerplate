<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Me;

use App\Http\Requests\Api\ApiFormRequest;

class KycMultipartUploadRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $maxKb = max(1, (int) config('kyc_id_verification.max_upload_kb', 5120));

        return [
            'session_id' => ['required', 'string', 'uuid'],
            'aadhaar_front' => ['required', 'file', 'image', 'max:' . $maxKb],
            'aadhaar_back' => ['required', 'file', 'image', 'max:' . $maxKb],
            'selfie' => ['required', 'file', 'image', 'max:' . $maxKb],
        ];
    }
}
