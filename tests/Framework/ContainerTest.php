<?php

declare(strict_types=1);

namespace Tests\Framework;

use Phantom\Container;
use Phantom\Exceptions\ContainerException;
use Tests\TestCase;

class ContainerTest extends TestCase
{
    public function testAutowiresConstructorDependencies(): void
    {
        $service = (new Container())->make(NeedsEngine::class);
        $this->assertTrue($service->engine instanceof Engine);
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $c = new Container();
        $c->singleton(Engine::class, fn () => new Engine());
        $this->assertTrue($c->make(Engine::class) === $c->make(Engine::class));
    }

    public function testBindReturnsNewInstanceEachTime(): void
    {
        $c = new Container();
        $c->bind(Engine::class, fn () => new Engine());
        $this->assertFalse($c->make(Engine::class) === $c->make(Engine::class));
    }

    public function testUnresolvableScalarThrows(): void
    {
        $this->assertThrows(ContainerException::class, fn () => (new Container())->make(NeedsScalar::class));
    }
}

class Engine
{
}

class NeedsEngine
{
    public function __construct(public Engine $engine)
    {
    }
}

class NeedsScalar
{
    public function __construct(public string $name)
    {
    }
}
