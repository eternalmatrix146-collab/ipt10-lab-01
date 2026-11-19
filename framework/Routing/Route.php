<?php

declare(strict_types=1);

namespace Phantom\Routing;

/**
 * A single route definition. Paths use {param} placeholders with optional
 * regex constraints: /product/{slug}, /orders/{id:\d+}.
 */
class Route
{
    private string $regex;

    /** @var string[] */
    private array $paramNames = [];

    /** @var string[] */
    private array $middleware = [];

    private ?string $name = null;

    public function __construct(
        private string $method,
        private string $path,
        private mixed $handler
    ) {
        $this->compile();
    }

    private function compile(): void
    {
        $pattern = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}#',
            function (array $m): string {
                $this->paramNames[] = $m[1];
                $constraint = $m[2] ?? '[^/]+';
                return '(?P<' . $m[1] . '>' . $constraint . ')';
            },
            $this->path
        );
        $this->regex = '#^' . $pattern . '$#';
    }

    /** @return array<string, string>|null route params when the path matches */
    public function match(string $path): ?array
    {
        if (!preg_match($this->regex, $path, $matches)) {
            return null;
        }
        $params = [];
        foreach ($this->paramNames as $name) {
            $params[$name] = rawurldecode($matches[$name]);
        }
        return $params;
    }

    public function middleware(string ...$middleware): self
    {
        $this->middleware = array_values(array_unique(array_merge($this->middleware, $middleware)));
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler(): mixed
    {
        return $this->handler;
    }

    /** @return string[] */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /** @param array<string, string|int> $params */
    public function url(array $params = []): string
    {
        $url = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::[^}]+)?\}#',
            function (array $m) use (&$params): string {
                if (!array_key_exists($m[1], $params)) {
                    throw new \InvalidArgumentException("Missing route parameter [{$m[1]}] for {$this->path}");
                }
                $value = rawurlencode((string) $params[$m[1]]);
                unset($params[$m[1]]);
                return $value;
            },
            $this->path
        );
        // Anything left over becomes the query string.
        return $params ? $url . '?' . http_build_query($params) : $url;
    }
}
