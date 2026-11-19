<?php

declare(strict_types=1);

namespace Phantom\Support;

/**
 * File-based key/value cache with TTLs (used for rate limiting and API responses).
 */
class Cache
{
    public function __construct(private string $dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->file($key);
        if (!is_file($file)) {
            return $default;
        }
        $payload = @unserialize((string) file_get_contents($file), ['allowed_classes' => false]);
        if (!is_array($payload) || ($payload['expires'] !== 0 && $payload['expires'] < time())) {
            @unlink($file);
            return $default;
        }
        return $payload['value'];
    }

    /** @param int $ttl seconds; 0 = forever */
    public function put(string $key, mixed $value, int $ttl = 0): void
    {
        $payload = serialize(['expires' => $ttl > 0 ? time() + $ttl : 0, 'value' => $value]);
        file_put_contents($this->file($key), $payload, LOCK_EX);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);
        if ($value === null) {
            $value = $callback();
            $this->put($key, $value, $ttl);
        }
        return $value;
    }

    public function forget(string $key): void
    {
        @unlink($this->file($key));
    }

    private function file(string $key): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . sha1($key) . '.cache';
    }
}
