<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Return a success JSON response
     */
    protected function successResponse(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $this->transformData($data);
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error JSON response
     */
    protected function errorResponse(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if (config('api.response.include_trace_in_debug') && app()->environment('local')) {
            $response['debug'] = [
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ];
        }

        return response()->json($response, $code);
    }

    /**
     * Return a paginated JSON response
     */
    /**
     * Return a paginated JSON response
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

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'data' => $resource instanceof JsonResource ? $resource : $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ],
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
            200
        );
    }

    /**
     * Return a created response (201)
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a no content response (204)
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a not found response (404)
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return an unauthorized response (401)
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a forbidden response (403)
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Return a validation error response (422)
     */
    protected function validationErrorResponse(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Return a server error response (500)
     */
    protected function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, 500);
    }

    /**
     * Transform data if needed
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
     * Add deprecation warning header
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
