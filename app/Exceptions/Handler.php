<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Helpers\HttpStatusCode;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponseBuilder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
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
     * @var array<class-string<Throwable>, LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
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
        $this->reportable(function (Throwable $e): void {
            if (app()->bound('sentry') && $this->shouldReport($e)) {
                app('sentry')->captureException($e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): JsonResponse|Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions with consistent envelope.
     */
    protected function handleApiException(Request $request, Throwable $exception): Response
    {
        $exception = $this->prepareException($exception);

        if ($exception instanceof ApiException) {
            return $exception->render();
        }

        if ($exception instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($exception, $request);
        }

        if ($exception instanceof NotFoundHttpException) {
            $previous = $exception->getPrevious();

            if ($previous instanceof ModelNotFoundException) {
                $message = $this->modelNotFoundMessage($previous);

                return ApiResponseBuilder::error(
                    $message,
                    HttpStatusCode::NOT_FOUND,
                    ApiResponseBuilder::ERROR_NOT_FOUND,
                    $message,
                    null
                );
            }

            return ApiResponseBuilder::error(
                'Endpoint not found',
                HttpStatusCode::NOT_FOUND,
                ApiResponseBuilder::ERROR_NOT_FOUND,
                'Endpoint not found',
                null
            );
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return ApiResponseBuilder::error(
                'Method not allowed',
                HttpStatusCode::METHOD_NOT_ALLOWED,
                ApiResponseBuilder::ERROR_METHOD_NOT_ALLOWED,
                'Method not allowed',
                null
            );
        }

        if ($exception instanceof AuthenticationException) {
            return ApiResponseBuilder::error(
                $exception->getMessage() ?: 'Unauthenticated',
                HttpStatusCode::UNAUTHORIZED,
                ApiResponseBuilder::ERROR_UNAUTHORIZED,
                $exception->getMessage() ?: 'Unauthenticated',
                null
            );
        }

        if ($exception instanceof AuthorizationException) {
            return ApiResponseBuilder::error(
                $exception->getMessage() ?: 'Forbidden',
                HttpStatusCode::FORBIDDEN,
                ApiResponseBuilder::ERROR_FORBIDDEN,
                $exception->getMessage() ?: 'Forbidden',
                null
            );
        }

        if ($exception instanceof SpatieUnauthorizedException) {
            $message = $exception->getMessage() ?: 'User does not have required permission.';

            return ApiResponseBuilder::error(
                $message,
                HttpStatusCode::FORBIDDEN,
                ApiResponseBuilder::ERROR_FORBIDDEN,
                $message,
                null
            );
        }

        if ($exception instanceof TooManyRequestsHttpException) {
            $response = ApiResponseBuilder::error(
                'Too many requests',
                HttpStatusCode::TOO_MANY_REQUESTS,
                ApiResponseBuilder::ERROR_TOO_MANY_REQUESTS,
                'Too many requests',
                null
            );

            return $response->withHeaders($exception->getHeaders());
        }

        if ($exception instanceof HttpException) {
            $message = $exception->getMessage() ?: HttpStatusCode::getText($exception->getStatusCode());

            return ApiResponseBuilder::error(
                $message,
                $exception->getStatusCode(),
                ApiResponseBuilder::ERROR_GENERIC,
                $message,
                null
            );
        }

        if ($exception instanceof QueryException) {
            return $this->handleQueryException($exception);
        }

        // Default server error: never expose internals to client
        Log::error('Unhandled API exception', [
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        return ApiResponseBuilder::error(
            'Internal server error',
            HttpStatusCode::INTERNAL_SERVER_ERROR,
            ApiResponseBuilder::ERROR_INTERNAL,
            'Internal server error',
            null
        );
    }

    /**
     * Convert validation exception to JSON response with envelope and field-level error.fields.
     */
    protected function convertValidationExceptionToResponse(ValidationException $exception, $request): Response
    {
        $message = $exception->getMessage() ?: 'Validation failed';
        $fields = ApiResponseBuilder::normalizeValidationFields($exception->errors());

        return ApiResponseBuilder::error(
            $message,
            HttpStatusCode::UNPROCESSABLE_ENTITY,
            ApiResponseBuilder::ERROR_VALIDATION,
            'Invalid input',
            $fields
        );
    }

    /**
     * Handle database query exceptions. Never expose SQL, bindings, or file paths to the client.
     */
    protected function handleQueryException(QueryException $exception): JsonResponse
    {
        Log::error('Database query exception', [
            'message' => $exception->getMessage(),
            'sql' => $exception->getSql(),
        ]);

        $errorCode = $exception->errorInfo[1] ?? null;

        return match ($errorCode) {
            1062 => ApiResponseBuilder::error(
                'Duplicate entry',
                HttpStatusCode::CONFLICT,
                ApiResponseBuilder::ERROR_CONFLICT,
                'Duplicate entry',
                null
            ),
            1451 => ApiResponseBuilder::error(
                'Cannot delete: referenced by other records',
                HttpStatusCode::CONFLICT,
                ApiResponseBuilder::ERROR_CONFLICT,
                'Cannot delete: referenced by other records',
                null
            ),
            1452 => ApiResponseBuilder::error(
                'Invalid reference',
                HttpStatusCode::BAD_REQUEST,
                ApiResponseBuilder::ERROR_BAD_REQUEST,
                'Invalid reference',
                null
            ),
            default => ApiResponseBuilder::error(
                'Database error',
                HttpStatusCode::INTERNAL_SERVER_ERROR,
                ApiResponseBuilder::ERROR_DB_ERROR,
                'Database error',
                null
            ),
        };
    }

    /**
     * Handle unauthenticated user.
     */
    protected function unauthenticated($request, AuthenticationException $exception): Response
    {
        return ApiResponseBuilder::error(
            $exception->getMessage() ?: 'Unauthenticated',
            HttpStatusCode::UNAUTHORIZED,
            ApiResponseBuilder::ERROR_UNAUTHORIZED,
            $exception->getMessage() ?: 'Unauthenticated',
            null
        );
    }

    private function modelNotFoundMessage(ModelNotFoundException $exception): string
    {
        return match ($exception->getModel()) {
            Role::class => 'Role not found',
            Payment::class => 'Payment not found',
            Package::class => 'Package not found',
            User::class => 'User not found',
            default => 'Resource not found',
        };
    }
}
