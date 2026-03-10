<?php

namespace Ddk\Api\Exceptions;

/**
 * Resource Not Found Exception
 */
class NotFoundException extends ApiException
{
    public function __construct(string $resource = 'Resource', int $id = null)
    {
        $message = $id !== null
            ? "{$resource} with ID {$id} not found."
            : "{$resource} not found.";

        parent::__construct(
            $message,
            404,
            'NOT_FOUND'
        );
    }
}
