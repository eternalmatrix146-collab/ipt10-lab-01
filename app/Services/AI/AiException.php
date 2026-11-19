<?php

declare(strict_types=1);

namespace App\Services\AI;

use RuntimeException;

/**
 * A failed NVIDIA NIM API call, classified so the UI can show a helpful message.
 * $kind: not_configured | authentication | permission | billing | rate_limited | overloaded |
 *        invalid_request | not_found | api_error | network
 */
class AiException extends RuntimeException
{
    public function __construct(
        string $message,
        private string $kind = 'api_error',
        private int $httpStatus = 0,
        private ?int $retryAfter = null
    ) {
        parent::__construct($message);
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /** Message safe to show to shoppers. */
    public function userMessage(): string
    {
        return match ($this->kind) {
            'not_configured' => 'Rook is offline — no NVIDIA API key is configured.',
            'authentication' => 'Rook could not sign in to NVIDIA NIM (invalid API key). Ask the shop admin to check NVIDIA_API_KEY_1..3.',
            'permission' => 'The NVIDIA API key is not allowed to use the configured model.',
            'billing' => 'Rook is resting — the shop\'s NVIDIA API credits are used up. Ask the shop admin to check the keys.',
            'rate_limited' => 'Rook is handling a lot of requests right now. Try again in ' . ($this->retryAfter ?? 30) . ' seconds.',
            'overloaded' => 'The Nemotron model is busy at the moment. Please try again shortly.',
            'network' => 'Rook could not reach NVIDIA NIM (network error).',
            'not_found' => 'The configured model was not found on NVIDIA NIM. Check NVIDIA_MODEL.',
            'invalid_request' => 'NVIDIA NIM rejected the request. Ask the shop admin to check the logs.',
            default => 'Rook hit an unexpected error talking to NVIDIA NIM.',
        };
    }
}
