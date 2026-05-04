<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rules\File;

class UploadCandidateProfileImageRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $maxKb = (int) config('user_images.max_upload_kb', 5120);

        return [
            'image' => ['required', File::image()->max($maxKb)],
        ];
    }
}
