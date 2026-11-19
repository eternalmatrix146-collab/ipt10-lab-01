<?php

declare(strict_types=1);

namespace Phantom\Routing;

use Phantom\Exceptions\HttpException;
use Phantom\Http\Request;

/**
 * Maps HTTP method + path to a handler.
 *
 *   $router->get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
 *   $router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function (Router $r) {
 *       $r->get('/', [DashboardController::class, 'index']);
 *   });
 */
class Router
{
    /** @var Route[] */
    private array $routes = [];

    /** @var array<string, Route> */
    private array $named = [];

    /** @var array<int, array{prefix: string, middleware: string[]}> */
    private array $groupStack = [];

    public function get(string $path, mixed $handler): Route
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): Route
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): Route
    {
        return $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, mixed $handler): Route
    {
        return $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, mixed $handler): Route
    {
        return $this->add('DELETE', $path, $handler);
    }

    public function add(string $method, string $path, mixed $handler): Route
    {
        $prefix = '';
        $middleware = [];
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'];
            $middleware = array_merge($middleware, $group['middleware']);
        }

        $route = new Route(strtoupper($method), Request::normalisePath($prefix . '/' . $path), $handler);
        if ($middleware) {
            $route->middleware(...$middleware);
        }
        $this->routes[] = $route;
        return $route;
    }

    /** @param array{prefix?: string, middleware?: string[]} $attributes */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = [
            'prefix' => isset($attributes['prefix']) ? '/' . trim($attributes['prefix'], '/') : '',
            'middleware' => $attributes['middleware'] ?? [],
        ];
        $callback($this);
        array_pop($this->groupStack);
    }

    /**
     * Finds the route for a request.
     *
     * @return array{0: Route, 1: array<string, string>}
     * @throws HttpException 404 when nothing matches, 405 when only the method is wrong
     */
    public function match(Request $request): array
    {
        $allowed = [];
        $method = $request->method();

        foreach ($this->routes as $route) {
            $params = $route->match($request->path());
            if ($params === null) {
                continue;
            }
            if ($route->getMethod() === $method || ($method === 'HEAD' && $route->getMethod() === 'GET')) {
                return [$route, $params];
            }
            $allowed[] = $route->getMethod();
        }

        if ($allowed) {
            throw new HttpException(405, 'Method Not Allowed', ['Allow' => implode(', ', array_unique($allowed))]);
        }
        throw new HttpException(404, 'Page not found');
    }

    /** @param array<string, string|int> $params */
    public function url(string $name, array $params = []): string
    {
        $route = $this->findNamed($name);
        if ($route === null) {
            throw new \InvalidArgumentException("Route [{$name}] is not defined.");
        }
        return $route->url($params);
    }

    private function findNamed(string $name): ?Route
    {
        if (!isset($this->named[$name])) {
            foreach ($this->routes as $route) {
                if ($route->getName() !== null) {
                    $this->named[$route->getName()] = $route;
                }
            }
        }
        return $this->named[$name] ?? null;
    }

    /** @return Route[] */
    public function routes(): array
    {
        return $this->routes;
    }
}
