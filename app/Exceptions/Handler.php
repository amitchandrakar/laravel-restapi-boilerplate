<?php

namespace App\Exceptions;

use App\Helpers\HttpStatusCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry') && $this->shouldReport($e)) {
                app('sentry')->captureException($e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        // Check if request expects JSON (API request)
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions
     */
    protected function handleApiException(
        Request $request,
        Throwable $exception
    ): \Symfony\Component\HttpFoundation\Response {
        $exception = $this->prepareException($exception);

        // Custom API Exception
        if ($exception instanceof ApiException) {
            return $exception->render();
        }

        // Validation Exception
        if ($exception instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($exception, $request);
        }

        // Model Not Found Exception
        if ($exception instanceof ModelNotFoundException) {
            return $this->notFoundResponse('Resource not found');
        }

        // Not Found Exception
        if ($exception instanceof NotFoundHttpException) {
            return $this->notFoundResponse('Endpoint not found');
        }

        // Method Not Allowed Exception
        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse('Method not allowed', HttpStatusCode::METHOD_NOT_ALLOWED);
        }

        // Authentication Exception
        if ($exception instanceof AuthenticationException) {
            return $this->errorResponse($exception->getMessage() ?: 'Unauthenticated', HttpStatusCode::UNAUTHORIZED);
        }

        // Authorization Exception
        if ($exception instanceof AuthorizationException) {
            return $this->errorResponse($exception->getMessage() ?: 'Forbidden', HttpStatusCode::FORBIDDEN);
        }

        // Too Many Requests Exception
        if ($exception instanceof TooManyRequestsHttpException) {
            return $this->errorResponse('Too many requests', HttpStatusCode::TOO_MANY_REQUESTS)->withHeaders(
                $exception->getHeaders()
            );
        }

        // HTTP Exception
        if ($exception instanceof HttpException) {
            return $this->errorResponse(
                $exception->getMessage() ?: HttpStatusCode::getText($exception->getStatusCode()),
                $exception->getStatusCode()
            );
        }

        // Query Exception (Database errors)
        if ($exception instanceof QueryException) {
            return $this->handleQueryException($exception);
        }

        // Default server error
        return $this->errorResponse(
            config('app.debug') ? $exception->getMessage() : 'Internal server error',
            HttpStatusCode::INTERNAL_SERVER_ERROR,
            config('app.debug') ? $this->getExceptionDetails($exception) : null
        );
    }

    /**
     * Convert validation exception to JSON response
     */
    protected function convertValidationExceptionToResponse(
        ValidationException $exception,
        $request
    ): \Symfony\Component\HttpFoundation\Response {
        return response()->json(
            [
                'success' => false,
                'message' => $exception->getMessage() ?: 'Validation failed',
                'errors' => $exception->errors(),
            ],
            HttpStatusCode::UNPROCESSABLE_ENTITY
        );
    }

    /**
     * Handle database query exceptions
     */
    protected function handleQueryException(QueryException $exception): JsonResponse
    {
        if (config('app.debug')) {
            return $this->errorResponse(
                'Database error: ' . $exception->getMessage(),
                HttpStatusCode::INTERNAL_SERVER_ERROR,
                [
                    'sql' => $exception->getSql(),
                    'bindings' => $exception->getBindings(),
                ]
            );
        }

        // Check for common database errors
        $errorCode = $exception->errorInfo[1] ?? null;

        return match ($errorCode) {
            1062 => $this->errorResponse('Duplicate entry', HttpStatusCode::CONFLICT),
            1451 => $this->errorResponse('Cannot delete: referenced by other records', HttpStatusCode::CONFLICT),
            1452 => $this->errorResponse('Invalid reference', HttpStatusCode::BAD_REQUEST),
            default => $this->errorResponse('Database error', HttpStatusCode::INTERNAL_SERVER_ERROR),
        };
    }

    /**
     * Get exception details for debugging
     */
    protected function getExceptionDetails(Throwable $exception): array
    {
        return [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage(),
            'trace' => collect($exception->getTrace())
                ->take(10)
                ->map(
                    fn($trace) => [
                        'file' => $trace['file'] ?? 'unknown',
                        'line' => $trace['line'] ?? 0,
                        'function' => $trace['function'],
                    ]
                )
                ->toArray(),
        ];
    }

    /**
     * Standard error response helper
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

        // Add correlation ID if available
        if (request()->hasHeader('X-Correlation-ID')) {
            $response['correlation_id'] = request()->header('X-Correlation-ID');
        }

        return response()->json($response, $code);
    }

    /**
     * Standard not found response helper
     */
    protected function notFoundResponse(string $message = 'Not found'): JsonResponse
    {
        return $this->errorResponse($message, HttpStatusCode::NOT_FOUND);
    }

    /**
     * Handle unauthenticated user
     */
    protected function unauthenticated(
        $request,
        AuthenticationException $exception
    ): \Symfony\Component\HttpFoundation\Response {
        // Always return JSON for API routes
        return $this->errorResponse($exception->getMessage() ?: 'Unauthenticated', HttpStatusCode::UNAUTHORIZED);
    }
}
