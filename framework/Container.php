<?php

declare(strict_types=1);

namespace Phantom;

use Closure;
use Phantom\Exceptions\ContainerException;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Tiny dependency-injection container with reflection-based autowiring.
 *
 *   $c->singleton(Mailer::class, fn ($c) => new Mailer($c->make(Config::class)));
 *   $controller = $c->make(ProductController::class); // dependencies resolved automatically
 */
class Container
{
    /** @var array<string, array{factory: Closure, shared: bool}> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function bind(string $id, Closure $factory): void
    {
        $this->bindings[$id] = ['factory' => $factory, 'shared' => false];
    }

    public function singleton(string $id, Closure $factory): void
    {
        $this->bindings[$id] = ['factory' => $factory, 'shared' => true];
    }

    public function instance(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]);
    }

    public function make(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            $object = ($this->bindings[$id]['factory'])($this);
            if ($this->bindings[$id]['shared']) {
                $this->instances[$id] = $object;
            }
            return $object;
        }

        return $this->autowire($id);
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new ContainerException("Cannot resolve [{$class}]: class does not exist.");
        }

        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Cannot resolve [{$class}]: it is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new ContainerException(
                    "Cannot resolve parameter \${$param->getName()} of [{$class}]."
                );
            }
        }

        return $reflection->newInstanceArgs($args);
    }
}
