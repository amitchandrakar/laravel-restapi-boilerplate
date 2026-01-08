<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected int $statusCode;
    protected mixed $errors;

    public function __construct(string $message = 'An error occurred', int $statusCode = 400, mixed $errors = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
        ];

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        if (config('app.debug') && $this->getPrevious()) {
            $response['debug'] = [
                'exception' => get_class($this->getPrevious()),
                'file' => $this->getPrevious()->getFile(),
                'line' => $this->getPrevious()->getLine(),
                'trace' => collect($this->getPrevious()->getTrace())->take(5)->toArray(),
            ];
        }

        return response()->json($response, $this->statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): mixed
    {
        return $this->errors;
    }
}
