<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reports;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class CandidateAreaReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'groupBy' => ['sometimes', 'string', Rule::in(['state', 'district', 'city', 'village'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }
}
