<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class SaveCandidateCareerEducationRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed|string|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return self::rulesWithPrefix('');
    }

    /**
     * Rules for career-education payload. Use prefix `career_education` for nested admin full-profile bodies.
     *
     * @return array<string, array<int, mixed|string|\Illuminate\Contracts\Validation\Rule>>
     */
    public static function rulesWithPrefix(string $prefix): array
    {
        $p = $prefix === '' ? '' : rtrim($prefix, '.') . '.';

        return [
            $p . 'occupation' => ['nullable', 'string', 'max:255'],
            $p . 'employer' => ['nullable', 'string', 'max:255'],
            $p . 'income' => ['nullable', 'numeric', 'min:0'],
            $p . 'marital_status' => ['nullable', 'string', 'max:64'],
            $p . 'qualifications' => ['nullable', 'array'],
            $p . 'qualifications.*.degree_id' => [
                'nullable',
                'integer',
                Rule::exists('degrees', 'id')->where('is_active', true),
            ],
            $p . 'qualifications.*.field_of_study' => ['nullable', 'string', 'max:255'],
            $p . 'qualifications.*.institution_name' => ['nullable', 'string', 'max:255'],
            $p . 'qualifications.*.year_of_graduation' => ['nullable', 'integer', 'min:1950', 'max:2100'],
        ];
    }
}
