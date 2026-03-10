<?php

namespace Ddk\Api\Exceptions;

use Exception;
use Throwable;

/**
 * Base API Exception
 *
 * All API-specific exceptions should extend this class.
 */
class ApiException extends Exception
{
    protected int $statusCode;
    protected string $errorCode;
    protected array $errors;

    public function __construct(
        string $message = 'An error occurred',
        int $statusCode = 400,
        string $errorCode = 'API_ERROR',
        array $errors = [],
        Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);

        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Convert exception to array for JSON response
     */
    public function toArray(): array
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ];

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }
}
