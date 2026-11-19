<?php

declare(strict_types=1);

namespace Phantom\Http\Client;

class HttpResponse
{
    /** @param array<string, string> $headers lower-cased header names */
    public function __construct(
        private int $status,
        private string $body,
        private array $headers = []
    ) {
    }

    public function status(): int
    {
        return $this->status;
    }

    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function json(): mixed
    {
        return json_decode($this->body, true);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
