<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use App\Support\HoroscopeBirthPlaceValidator;
use Illuminate\Contracts\Validation\Validator;

class SaveCandidateHoroscopeRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if (!$this->has('time_of_birth')) {
            return;
        }
        $t = $this->input('time_of_birth');
        if (!is_string($t)) {
            return;
        }
        $t = trim($t);
        if ($t === '') {
            return;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $t)) {
            $this->merge(['time_of_birth' => $t . ':00']);
        }
    }

    public function rules(): array
    {
        return HoroscopeBirthPlaceValidator::rules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            HoroscopeBirthPlaceValidator::validateConsistency($validator, $this->all(), '');
        });
    }
}
