<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;

class ImportCandidatesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.candidates.import') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = max(1, (int) config('api.candidates.import_max_file_kb', 2048));

        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:' . $maxKb],
        ];
    }
}
