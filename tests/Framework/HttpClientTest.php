<?php

declare(strict_types=1);

namespace Tests\Framework;

use Phantom\Http\Client\HttpClient;
use Phantom\Http\Client\HttpClientException;
use Phantom\Http\Client\HttpResponse;
use Tests\TestCase;

class HttpClientTest extends TestCase
{
    public function testSendsJsonAndHeaders(): void
    {
        $seen = [];
        HttpClient::fake(function (string $method, string $url, array $headers, ?string $body) use (&$seen) {
            $seen = compact('method', 'url', 'headers', 'body');
            return new HttpResponse(200, '{"ok":true}');
        });
        $res = (new HttpClient())->withHeaders(['x-api-key' => 'k'])->postJson('https://api.test/v1', ['a' => 1]);

        $this->assertTrue($res->ok());
        $this->assertSame(['ok' => true], $res->json());
        $this->assertSame('POST', $seen['method']);
        $this->assertSame('k', $seen['headers']['x-api-key']);
        $this->assertSame('application/json', $seen['headers']['Content-Type']);
        $this->assertSame('{"a":1}', $seen['body']);
    }

    public function testRetriesOn429And5xxThenSucceeds(): void
    {
        $calls = 0;
        HttpClient::fake(function () use (&$calls) {
            $calls++;
            return match ($calls) {
                1 => new HttpResponse(429, '{}', ['retry-after' => '1']),
                2 => new HttpResponse(503, '{}'),
                default => new HttpResponse(200, '{"done":1}'),
            };
        });
        $res = (new HttpClient())->retry(2)->get('https://api.test/x', ['q' => 'mask']);
        $this->assertSame(3, $calls);
        $this->assertSame(200, $res->status());
    }

    public function testDoesNotRetryClientErrors(): void
    {
        $calls = 0;
        HttpClient::fake(function () use (&$calls) {
            $calls++;
            return new HttpResponse(400, '{"error":"bad"}');
        });
        $res = (new HttpClient())->retry(3)->get('https://api.test/x');
        $this->assertSame(1, $calls);
        $this->assertSame(400, $res->status());
    }

    public function testNetworkErrorsAreRetriedThenThrown(): void
    {
        $calls = 0;
        HttpClient::fake(function () use (&$calls) {
            $calls++;
            throw new HttpClientException('timeout');
        });
        $this->assertThrows(HttpClientException::class, fn () => (new HttpClient())->retry(1)->get('https://api.test/x'));
        $this->assertSame(2, $calls);
    }
}
