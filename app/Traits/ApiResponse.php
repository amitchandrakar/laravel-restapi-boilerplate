<?php

declare(strict_types=1);

namespace App\Traits;

use App\Support\ApiResponseBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Return a success JSON response with full envelope.
     */
    protected function successResponse(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        $transformed = $data !== null ? $this->transformData($data) : null;

        return ApiResponseBuilder::success($transformed, $message, $code, null);
    }

    /**
     * Return an error JSON response with full envelope.
     *
     * @param  array{field: string, message: string}[]|null  $fields
     */
    protected function errorResponse(
        string $message,
        int $code = 400,
        ?string $errorCode = null,
        ?string $details = null,
        ?array $fields = null
    ): JsonResponse {
        return ApiResponseBuilder::error($message, $code, $errorCode, $details, $fields);
    }

    /**
     * Return a paginated JSON response (single list; pagination in meta).
     */
    protected function paginatedResponse(
        LengthAwarePaginator|JsonResource $paginator,
        string $message = 'Success'
    ): JsonResponse {
        $resource = $paginator;
        if ($paginator instanceof JsonResource) {
            $paginator = $paginator->resource;
        }
        if (!$paginator instanceof LengthAwarePaginator) {
            throw new \InvalidArgumentException('Pagination data not found.');
        }
        $items = $resource instanceof JsonResource ? $resource->resolve() : $paginator->items();
        $metaExtra = ['pagination' => ApiResponseBuilder::paginationFromPaginator($paginator)];

        return ApiResponseBuilder::success($items, $message, 200, $metaExtra);
    }

    /**
     * Build a list object with items and pagination (for responses with multiple paginated lists).
     * Use in data e.g. data.products = $this->paginatedList($productsPaginator).
     */
    protected function paginatedList(LengthAwarePaginator|JsonResource $paginator): array
    {
        return ApiResponseBuilder::paginatedList($paginator);
    }

    /**
     * Return a created response (201).
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a no content response (204). No envelope per HTTP semantics.
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a not found response (404).
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404, ApiResponseBuilder::ERROR_NOT_FOUND, $message, null);
    }

    /**
     * Return an unauthorized response (401).
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401, ApiResponseBuilder::ERROR_UNAUTHORIZED, $message, null);
    }

    /**
     * Return a forbidden response (403).
     */
    protected function forbiddenResponse(string $message = 'User does not have required permission.'): JsonResponse
    {
        return $this->errorResponse($message, 403, ApiResponseBuilder::ERROR_FORBIDDEN, $message, null);
    }

    /**
     * Return a validation error response (422) with field-level details.
     *
     * @param  mixed  $errors  Laravel validation errors array (field => [messages]) or already normalized [ {field, message}, ... ]
     */
    protected function validationErrorResponse(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        $fields = ApiResponseBuilder::normalizeValidationFields(is_array($errors) && !empty($errors) ? $errors : []);

        return $this->errorResponse($message, 422, ApiResponseBuilder::ERROR_VALIDATION, $message, $fields);
    }

    /**
     * Return a server error response (500).
     */
    protected function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, 500, ApiResponseBuilder::ERROR_INTERNAL, $message, null);
    }

    /**
     * Transform data if needed (JsonResource -> resolve, LengthAwarePaginator -> items).
     */
    private function transformData(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve();
        }
        if ($data instanceof LengthAwarePaginator) {
            return $data->items();
        }

        return $data;
    }

    /**
     * Add deprecation warning header.
     */
    protected function withDeprecationWarning(
        JsonResponse $response,
        string $version,
        ?string $sunsetDate = null
    ): JsonResponse {
        $response->header('X-API-Deprecation', 'true');
        $response->header('X-API-Deprecated-Version', $version);
        if ($sunsetDate) {
            $response->header('X-API-Sunset-Date', $sunsetDate);
        }
        $message = config("api.deprecation.{$version}.message");
        if ($message) {
            $response->header('X-API-Deprecation-Message', $message);
        }

        return $response;
    }
}
