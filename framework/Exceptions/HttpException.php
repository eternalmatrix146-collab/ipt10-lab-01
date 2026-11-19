<?php

declare(strict_types=1);

namespace Phantom\Exceptions;

use RuntimeException;
use Throwable;

/**
 * An exception that maps directly to an HTTP status code (404, 403, 405, 419, 429...).
 */
class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(
        private int $status,
        string $message = '',
        private array $headers = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
