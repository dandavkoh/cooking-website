<?php

namespace Ddk\Api\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Ddk\Api\Exceptions\ValidationException;

/**
 * Base API Request Validator
 *
 * Abstract class for validating API requests with custom rules
 * and messages.
 */
abstract class BaseRequest
{
    protected array $data;
    protected array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Define validation rules
     */
    abstract public function rules(): array;

    /**
     * Define custom validation messages
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Define custom attribute names
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Validate the request data
     *
     * @throws ValidationException
     */
    public function validate(): array
    {
        $validator = Validator::make(
            $this->data,
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    /**
     * Get a specific input value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Get all input data
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Check if a key exists in the data
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
}
