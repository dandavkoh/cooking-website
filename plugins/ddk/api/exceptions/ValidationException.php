<?php

namespace Ddk\Api\Exceptions;

/**
 * Validation Exception
 *
 * Thrown when request validation fails.
 */
class ValidationException extends ApiException
{
    public function __construct(array $errors, string $message = null)
    {
        parent::__construct(
            $message ?? trans('ddk.api::lang.errors.validation_failed'),
            422,
            'VALIDATION_ERROR',
            $errors
        );
    }
}
