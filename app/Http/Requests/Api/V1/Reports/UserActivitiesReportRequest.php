<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reports;

use App\Http\Requests\Api\ApiFormRequest;

class UserActivitiesReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'userId' => ['sometimes', 'integer', 'exists:users,id'],
            'activityType' => ['sometimes', 'string', 'max:128'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
