<?php

namespace Ddk\Api\Classes;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Ddk\Api\Exceptions\ApiException;

/**
 * Base API Controller
 *
 * Provides common functionality for all API controllers including
 * standardized response formatting, error handling, and pagination.
 */
abstract class BaseApiController extends Controller
{
    /**
     * HTTP status codes
     */
    protected const HTTP_OK = 200;
    protected const HTTP_CREATED = 201;
    protected const HTTP_NO_CONTENT = 204;
    protected const HTTP_BAD_REQUEST = 400;
    protected const HTTP_UNAUTHORIZED = 401;
    protected const HTTP_FORBIDDEN = 403;
    protected const HTTP_NOT_FOUND = 404;
    protected const HTTP_UNPROCESSABLE_ENTITY = 422;
    protected const HTTP_INTERNAL_ERROR = 500;

    /**
     * Return a success response
     */
    protected function success(mixed $data = null, string $message = null, int $statusCode = self::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a created response
     */
    protected function created(mixed $data = null, string $message = null): JsonResponse
    {
        return $this->success($data, $message, self::HTTP_CREATED);
    }

    /**
     * Return an error response
     */
    protected function error(
        string $message,
        int $statusCode = self::HTTP_BAD_REQUEST,
        array $errors = [],
        string $errorCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response
     */
    protected function validationError(array $errors, string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? trans('ddk.api::lang.errors.validation_failed'),
            self::HTTP_UNPROCESSABLE_ENTITY,
            $errors,
            'VALIDATION_ERROR'
        );
    }

    /**
     * Return a not found response
     */
    protected function notFound(string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? trans('ddk.api::lang.errors.not_found'),
            self::HTTP_NOT_FOUND,
            [],
            'NOT_FOUND'
        );
    }

    /**
     * Return an unauthorized response
     */
    protected function unauthorized(string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? trans('ddk.api::lang.errors.unauthorized'),
            self::HTTP_UNAUTHORIZED,
            [],
            'UNAUTHORIZED'
        );
    }

    /**
     * Return a forbidden response
     */
    protected function forbidden(string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? trans('ddk.api::lang.errors.forbidden'),
            self::HTTP_FORBIDDEN,
            [],
            'FORBIDDEN'
        );
    }

    /**
     * Return a server error response
     */
    protected function serverError(string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? trans('ddk.api::lang.errors.server_error'),
            self::HTTP_INTERNAL_ERROR,
            [],
            'SERVER_ERROR'
        );
    }

    /**
     * Format a paginated response
     */
    protected function paginated($paginator, callable $transformer = null): JsonResponse
    {
        $items = $paginator->items();

        if ($transformer !== null) {
            $items = array_map($transformer, $items);
        }

        return $this->success([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
