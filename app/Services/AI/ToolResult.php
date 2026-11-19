<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * What a tool hands back: `data` goes back to the model as the tool result; `cards`
 * (product summaries) and `effects` (e.g. new cart count) go to the browser.
 */
final class ToolResult
{
    /**
     * @param array<string, mixed>|string $data
     * @param array<int, array<string, mixed>> $cards
     * @param array<string, mixed> $effects
     */
    public function __construct(
        public array|string $data,
        public bool $isError = false,
        public array $cards = [],
        public array $effects = [],
        public string $summary = ''
    ) {
    }

    /**
     * @param array<string, mixed>|string $data
     * @param array<int, array<string, mixed>> $cards
     * @param array<string, mixed> $effects
     */
    public static function ok(array|string $data, array $cards = [], array $effects = [], string $summary = ''): self
    {
        return new self($data, false, $cards, $effects, $summary);
    }

    public static function error(string $message): self
    {
        return new self(['error' => $message], true, [], [], $message);
    }

    public function content(): string
    {
        return is_string($this->data)
            ? $this->data
            : (string) json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
