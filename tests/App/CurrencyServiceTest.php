<?php

declare(strict_types=1);

namespace Tests\App;

use App\Services\CurrencyService;
use Phantom\Database\DB;
use Phantom\Http\Client\HttpClient;
use Phantom\Http\Client\HttpClientException;
use Phantom\Http\Client\HttpResponse;
use Phantom\Support\Logger;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    private function service(): CurrencyService
    {
        return new CurrencyService(new HttpClient(), new Logger(sys_get_temp_dir() . '/phantom-test-logs'));
    }

    public function testFetchesLiveRatesAndCachesThemInTheDatabase(): void
    {
        $this->useSqlite();
        $requested = '';
        HttpClient::fake(function ($method, $url) use (&$requested) {
            $requested = $url;
            return new HttpResponse(200, '{"amount":1.0,"base":"PHP","date":"2026-11-04","rates":{"USD":0.0175,"EUR":0.016,"JPY":2.5,"GBP":0.013,"KRW":23.0,"SGD":0.023}}');
        });

        $service = $this->service();
        $this->assertEquals(17.5, $service->convert(1000, 'USD'));
        $this->assertSame('$17.50', $service->format(1000, 'USD'));
        $this->assertSame('¥2,500', $service->format(1000, 'JPY'));
        $this->assertSame('live', $service->info()['source']);
        $this->assertStringContains('base=PHP', $requested);
        $this->assertSame(6, DB::table('exchange_rates')->count());

        // A new instance reads the fresh rows instead of calling the API again.
        HttpClient::fake(fn () => throw new HttpClientException('should not be called'));
        $cached = $this->service();
        $this->assertEquals(0.0175, $cached->rate('USD'));
        $this->assertSame('cache', $cached->info()['source']);
    }

    public function testFallsBackToStaleRatesWhenApiIsDown(): void
    {
        $db = $this->useSqlite();
        $db->table('exchange_rates')->insert([
            'base' => 'PHP', 'currency' => 'USD', 'rate' => 0.02, 'rate_date' => '2026-01-01',
            'fetched_at' => '2026-01-01 00:00:00', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00',
        ]);
        HttpClient::fake(fn () => throw new HttpClientException('down'));

        $service = $this->service();
        $this->assertEquals(0.02, $service->rate('USD'));
        $this->assertSame('stale', $service->info()['source']);
    }

    public function testUsesBuiltInRatesWhenNothingIsAvailable(): void
    {
        $this->useSqlite();
        HttpClient::fake(fn () => new HttpResponse(500, 'oops'));
        $service = $this->service();
        $this->assertSame('fallback', $service->info()['source']);
        $this->assertTrue($service->rate('USD') > 0);
        $this->assertEquals(1.0, $service->rate('PHP'));
    }
}
