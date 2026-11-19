<?php

declare(strict_types=1);

namespace Phantom\Session;

/**
 * Session wrapper with flash data. Flash values set during one request are
 * readable during the next request only (used for "Order placed!" messages,
 * validation errors and old form input).
 *
 * When running from the CLI/tests, an in-memory array is used instead of $_SESSION.
 */
class Session
{
    /** @var array<string, mixed> */
    private array $memory = [];

    private bool $started = false;

    public function __construct(private string $name = 'phantom_session', private bool $useNative = true)
    {
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }
        if ($this->useNative && PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_name($this->name);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
            session_start();
        }
        $this->started = true;
        $this->ageFlashData();
    }

    /** @return array<string, mixed> */
    private function &store(): array
    {
        if ($this->useNative && session_status() === PHP_SESSION_ACTIVE) {
            return $_SESSION;
        }
        return $this->memory;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store()[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $store = &$this->store();
        $store[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->store()[$key]);
    }

    public function forget(string $key): void
    {
        $store = &$this->store();
        unset($store[$key]);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    public function flash(string $key, mixed $value): void
    {
        $store = &$this->store();
        $store['_flash']['new'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $store = $this->store();
        return $store['_flash']['old'][$key] ?? $store['_flash']['new'][$key] ?? $default;
    }

    /** Moves "new" flash data to "old" so it survives exactly one more request. */
    private function ageFlashData(): void
    {
        $store = &$this->store();
        $store['_flash']['old'] = $store['_flash']['new'] ?? [];
        $store['_flash']['new'] = [];
    }

    public function token(): string
    {
        if (!$this->has('_token')) {
            $this->put('_token', bin2hex(random_bytes(32)));
        }
        return (string) $this->get('_token');
    }

    public function id(): string
    {
        if ($this->useNative && session_status() === PHP_SESSION_ACTIVE) {
            return (string) session_id();
        }
        if (!$this->has('_cli_id')) {
            $this->put('_cli_id', bin2hex(random_bytes(16)));
        }
        return (string) $this->get('_cli_id');
    }

    /** New session id after login, preventing session-fixation attacks. */
    public function regenerate(): void
    {
        if ($this->useNative && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function invalidate(): void
    {
        $store = &$this->store();
        $store = [];
        $this->regenerate();
    }
}
