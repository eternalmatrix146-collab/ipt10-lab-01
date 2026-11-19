<?php

declare(strict_types=1);

namespace Tests\Framework;

use Phantom\Http\Request;
use Tests\TestCase;

class RequestTest extends TestCase
{
    public function testPathIsNormalised(): void
    {
        $this->assertSame('/shop', (new Request('GET', 'shop/'))->path());
        $this->assertSame('/a/b', (new Request('GET', '//a///b//'))->path());
        $this->assertSame('/', (new Request('GET', ''))->path());
    }

    public function testInputPrefersBodyThenQuery(): void
    {
        $request = new Request('POST', '/x', ['q' => 'query', 'page' => '2'], ['q' => 'body']);
        $this->assertSame('body', $request->input('q'));
        $this->assertSame('2', $request->input('page'));
        $this->assertSame('fallback', $request->input('missing', 'fallback'));
        $this->assertSame(['q' => 'body'], $request->only('q'));
    }

    public function testWantsJsonForApiPathsAndAjax(): void
    {
        $this->assertTrue((new Request('GET', '/api/products'))->wantsJson());
        $this->assertTrue((new Request('GET', '/cart', [], [], ['accept' => 'application/json']))->wantsJson());
        $this->assertTrue((new Request('GET', '/cart', [], [], ['x-requested-with' => 'XMLHttpRequest']))->wantsJson());
        $this->assertFalse((new Request('GET', '/cart', [], [], ['accept' => 'text/html']))->wantsJson());
    }

    public function testDetectBasePathForSubfolderInstalls(): void
    {
        // Apache with document root at the project folder (rewrite into /public)
        $this->assertSame('/midnight', Request::detectBasePath('/midnight/shop', '/midnight/public/index.php'));
        // Apache pointing at /public directly
        $this->assertSame('/midnight/public', Request::detectBasePath('/midnight/public/shop', '/midnight/public/index.php'));
        // Web root
        $this->assertSame('', Request::detectBasePath('/shop', '/index.php'));
    }
}
