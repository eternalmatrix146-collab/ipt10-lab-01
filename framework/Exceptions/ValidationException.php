<?php

declare(strict_types=1);

namespace Phantom\Exceptions;

use RuntimeException;

/**
 * Thrown by Validator::validate(). The kernel turns it into a redirect back with
 * errors + old input (HTML) or a 422 JSON response (API).
 */
class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string[]> $errors
     * @param array<string, mixed> $input
     */
    public function __construct(private array $errors, private array $input = [])
    {
        parent::__construct('The given data was invalid.', 422);
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function input(): array
    {
        return $this->input;
    }
}
