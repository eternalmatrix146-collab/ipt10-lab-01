<?php

declare(strict_types=1);

namespace Tests\Framework;

use Phantom\Exceptions\HttpException;
use Phantom\Http\Request;
use Phantom\Routing\Router;
use Tests\TestCase;

class RouterTest extends TestCase
{
    private function request(string $method, string $path): Request
    {
        return new Request($method, $path);
    }

    public function testMatchesStaticRoute(): void
    {
        $router = new Router();
        $router->get('/shop', 'ShopController@index');
        [$route, $params] = $router->match($this->request('GET', '/shop'));
        $this->assertSame('/shop', $route->getPath());
        $this->assertSame([], $params);
    }

    public function testExtractsNamedParameters(): void
    {
        $router = new Router();
        $router->get('/product/{slug}', 'P@show');
        [, $params] = $router->match($this->request('GET', '/product/crimson-mask'));
        $this->assertSame(['slug' => 'crimson-mask'], $params);
    }

    public function testRegexConstraintRejectsNonMatchingValues(): void
    {
        $router = new Router();
        $router->get('/orders/{id:\d+}', 'O@show');
        [, $params] = $router->match($this->request('GET', '/orders/42'));
        $this->assertSame('42', $params['id']);
        $this->assertThrows(HttpException::class, fn () => $router->match($this->request('GET', '/orders/abc')));
    }

    public function testUnknownPathIs404(): void
    {
        $router = new Router();
        $router->get('/', 'H@index');
        $e = $this->assertThrows(HttpException::class, fn () => $router->match($this->request('GET', '/missing')));
        $this->assertSame(404, $e->getStatus());
    }

    public function testWrongMethodIs405WithAllowHeader(): void
    {
        $router = new Router();
        $router->post('/cart/add', 'C@add');
        $e = $this->assertThrows(HttpException::class, fn () => $router->match($this->request('GET', '/cart/add')));
        $this->assertSame(405, $e->getStatus());
        $this->assertSame('POST', $e->getHeaders()['Allow']);
    }

    public function testGroupsApplyPrefixAndMiddleware(): void
    {
        $router = new Router();
        $router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function (Router $r): void {
            $r->get('/orders', 'A@index')->middleware('throttle:5,60');
        });
        [$route] = $router->match($this->request('GET', '/admin/orders'));
        $this->assertSame('/admin/orders', $route->getPath());
        $this->assertSame(['auth', 'admin', 'throttle:5,60'], $route->getMiddleware());
    }

    public function testNamedRouteUrlGeneration(): void
    {
        $router = new Router();
        $router->get('/orders/{reference}', 'O@show')->name('orders.show');
        $this->assertSame('/orders/MM-1', $router->url('orders.show', ['reference' => 'MM-1']));
        $this->assertSame('/orders/MM-1?tab=items', $router->url('orders.show', ['reference' => 'MM-1', 'tab' => 'items']));
    }

    public function testMethodSpoofingViaHiddenField(): void
    {
        $request = new Request('POST', '/admin/products/3', [], ['_method' => 'DELETE']);
        $this->assertSame('DELETE', $request->method());
    }
}
