<?php

declare(strict_types=1);

namespace Phantom\Support;

/**
 * Loads KEY=VALUE pairs from a .env file into the process environment.
 * Real environment variables always win over values in the file.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = self::stripQuotes($value);

            if (getenv($key) !== false && getenv($key) !== '') {
                $value = (string) getenv($key);
            }

            self::$values[$key] = $value;
            $_ENV[$key] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$values[$key] ?? $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }

    public static function set(string $key, string $value): void
    {
        self::$values[$key] = $value;
        $_ENV[$key] = $value;
    }

    private static function stripQuotes(string $value): string
    {
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            return substr($value, 1, -1);
        }
        // Allow inline comments after unquoted values: KEY=value # comment
        $hash = strpos($value, ' #');
        return $hash === false ? $value : rtrim(substr($value, 0, $hash));
    }
}
