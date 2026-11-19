<?php

declare(strict_types=1);

namespace Tests\Framework;

use Phantom\Container;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Middleware\MiddlewareInterface;
use Phantom\Middleware\Pipeline;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    public function testMiddlewareRunsInOrderAroundTheDestination(): void
    {
        $log = [];
        // A regular closure (not fn) so $log is shared by reference.
        $make = static function (string $name) use (&$log) {
            return new class ($name, $log) implements MiddlewareInterface {
            public function __construct(private string $name, private array &$log)
            {
            }

            public function handle(Request $request, callable $next): Response
            {
                $this->log[] = "{$this->name}:before";
                $response = $next($request);
                $this->log[] = "{$this->name}:after";
                return $response;
            }
            };
        };

        $pipeline = new Pipeline(new Container());
        $response = $pipeline->through([$make('a'), $make('b')])->then(new Request('GET', '/'), function () use (&$log) {
            $log[] = 'controller';
            return Response::html('ok');
        });

        $this->assertSame('ok', $response->content());
        $this->assertSame(['a:before', 'b:before', 'controller', 'b:after', 'a:after'], $log);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $blocker = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return Response::redirect('/login');
            }
        };
        $reached = false;
        $response = (new Pipeline(new Container()))->through([$blocker])->then(new Request('GET', '/'), function () use (&$reached) {
            $reached = true;
            return Response::html('secret');
        });
        $this->assertFalse($reached);
        $this->assertTrue($response->isRedirect());
        $this->assertSame('/login', $response->header('Location'));
    }

    public function testAliasWithParametersIsConstructed(): void
    {
        $pipeline = new Pipeline(new Container(), ['tag' => TagMiddleware::class]);
        $response = $pipeline->through(['tag:hello,world'])->then(new Request('GET', '/'), fn () => Response::html(''));
        $this->assertSame('hello-world', $response->header('X-Tag'));
    }
}

class TagMiddleware implements MiddlewareInterface
{
    public function __construct(private string $a = '', private string $b = '')
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        return $next($request)->withHeader('X-Tag', "{$this->a}-{$this->b}");
    }
}
