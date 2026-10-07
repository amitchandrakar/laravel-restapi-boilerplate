<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use Illuminate\Foundation\Http\FormRequest;

class ReportProfileSpamRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
