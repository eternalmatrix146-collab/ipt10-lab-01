<?php

declare(strict_types=1);

namespace Phantom\Http;

/**
 * Immutable-ish wrapper around the PHP superglobals.
 *
 * Normalises the path (strips the base path + query string), supports
 * method spoofing through a hidden "_method" field, and merges JSON bodies
 * into input() so controllers treat forms and fetch() calls the same way.
 */
class Request
{
    /** @var array<string, string> */
    private array $routeParams = [];

    /** @var array<string, mixed> values attached by middleware (e.g. the authenticated user) */
    private array $attributes = [];

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     * @param array<string, string> $cookies
     * @param array<string, mixed> $server
     */
    public function __construct(
        private string $method,
        private string $path,
        private array $query = [],
        private array $body = [],
        private array $headers = [],
        private array $cookies = [],
        private array $server = [],
        private string $basePath = '',
        private string $rawBody = ''
    ) {
        $this->method = strtoupper($method);
        $this->path = self::normalisePath($path);

        // HTML forms can only send GET/POST, so allow <input name="_method" value="DELETE">.
        if ($this->method === 'POST' && isset($this->body['_method'])) {
            $spoofed = strtoupper((string) $this->body['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                $this->method = $spoofed;
            }
        }
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        $raw = (string) file_get_contents('php://input');
        $body = $_POST;
        if (str_contains($headers['content-type'] ?? '', 'application/json') && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        $uriPath = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        // The built-in dev server always serves from the web root, and for URLs that
        // look like files (/art/product/1.svg) it reports the URL itself as SCRIPT_NAME.
        $basePath = PHP_SAPI === 'cli-server'
            ? ''
            : self::detectBasePath($uriPath, (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $path = substr($uriPath, strlen($basePath)) ?: '/';

        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $path,
            $_GET,
            $body,
            $headers,
            $_COOKIE,
            $_SERVER,
            $basePath,
            $raw
        );
    }

    /**
     * Works for `php -S` (base ""), Apache pointing at /public, and Apache
     * pointing at the project root with a rewrite into /public.
     */
    public static function detectBasePath(string $uriPath, string $scriptName): string
    {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($scriptDir === '' || $scriptDir === '.') {
            return '';
        }
        if (str_starts_with($uriPath, $scriptDir)) {
            return $scriptDir;
        }
        $parent = rtrim(str_replace('\\', '/', dirname($scriptDir)), '/');
        if ($parent !== '' && $parent !== '.' && str_starts_with($uriPath, $parent)) {
            return $parent;
        }
        return '';
    }

    public static function normalisePath(string $path): string
    {
        $path = '/' . trim(preg_replace('#/+#', '/', $path) ?? '/', '/');
        return $path;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    /** Full URL path of the current request including the base path and query string. */
    public function fullPath(): string
    {
        $qs = $this->query ? '?' . http_build_query($this->query) : '';
        return $this->basePath . $this->path . $qs;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /** @return array<string, mixed> */
    public function only(string ...$keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    public function has(string $key): bool
    {
        $value = $this->input($key);
        return $value !== null && $value !== '';
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function cookie(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('x-requested-with', '')) === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        return $this->isAjax()
            || str_contains($this->header('accept', ''), 'application/json')
            || str_starts_with($this->path, '/api/');
    }

    /** @param array<string, string> $params */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, ?string $default = null): ?string
    {
        return $this->routeParams[$key] ?? $default;
    }

    /** @return array<string, string> */
    public function params(): array
    {
        return $this->routeParams;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
