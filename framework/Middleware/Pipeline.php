<?php

declare(strict_types=1);

namespace Phantom\Middleware;

use Phantom\Container;
use Phantom\Http\Request;
use Phantom\Http\Response;

/**
 * Sends a request through a stack of middleware, then into the destination.
 *
 * Entries may be instances, class names, aliases ("auth") or aliases with
 * constructor arguments ("throttle:20,60").
 */
class Pipeline
{
    /** @var array<int, MiddlewareInterface|string> */
    private array $stack = [];

    /** @param array<string, class-string<MiddlewareInterface>> $aliases */
    public function __construct(
        private Container $container,
        private array $aliases = []
    ) {
    }

    /** @param array<int, MiddlewareInterface|string> $middleware */
    public function through(array $middleware): self
    {
        $this->stack = $middleware;
        return $this;
    }

    public function then(Request $request, callable $destination): Response
    {
        $next = $destination;
        foreach (array_reverse($this->stack) as $entry) {
            $middleware = $this->resolve($entry);
            $next = static fn (Request $req): Response => $middleware->handle($req, $next);
        }
        return $next($request);
    }

    private function resolve(MiddlewareInterface|string $entry): MiddlewareInterface
    {
        if ($entry instanceof MiddlewareInterface) {
            return $entry;
        }

        $params = [];
        if (str_contains($entry, ':')) {
            [$entry, $paramString] = explode(':', $entry, 2);
            $params = explode(',', $paramString);
        }

        $class = $this->aliases[$entry] ?? $entry;
        $middleware = $params ? new $class(...$params) : $this->container->make($class);

        if (!$middleware instanceof MiddlewareInterface) {
            throw new \LogicException("[{$class}] must implement " . MiddlewareInterface::class);
        }
        return $middleware;
    }
}
