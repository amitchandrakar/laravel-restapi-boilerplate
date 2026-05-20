<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Support\ApiResponseBuilder;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

abstract class ApiFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $fields = ApiResponseBuilder::normalizeValidationFields($validator->errors()->toArray());

        throw new HttpResponseException(
            ApiResponseBuilder::error(
                'Validation failed.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                ApiResponseBuilder::ERROR_VALIDATION,
                'Validation failed.',
                $fields
            )
        );
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            ApiResponseBuilder::error(
                'Forbidden',
                JsonResponse::HTTP_FORBIDDEN,
                ApiResponseBuilder::ERROR_FORBIDDEN,
                'Forbidden',
                null
            )
        );
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [];
    }
}
