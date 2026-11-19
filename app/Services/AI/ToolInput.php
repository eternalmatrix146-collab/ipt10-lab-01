<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Typed, bounds-checked access to the arguments the model sent for a tool call.
 * Model output is untrusted input: every value is validated before use.
 */
final class ToolInput
{
    /** @param array<string, mixed> $values */
    public function __construct(private array $values)
    {
    }

    public function string(string $key, string $default = '', int $maxLength = 200): string
    {
        $value = $this->values[$key] ?? $default;
        if (!is_scalar($value)) {
            return $default;
        }
        return mb_substr(trim((string) $value), 0, $maxLength);
    }

    public function int(string $key, int $default, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
    {
        $value = $this->values[$key] ?? null;
        $int = is_numeric($value) ? (int) $value : $default;
        return max($min, min($max, $int));
    }

    public function float(string $key, ?float $default = null): ?float
    {
        $value = $this->values[$key] ?? null;
        return is_numeric($value) ? (float) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->values[$key] ?? $default;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public function required(string $key): string
    {
        $value = $this->string($key);
        if ($value === '') {
            throw new ToolError("Missing required argument \"{$key}\".");
        }
        return $value;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->values;
    }
}
