<?php

declare(strict_types=1);

namespace App\Services;

use Phantom\Database\DB;
use Phantom\Http\Client\HttpClient;
use Phantom\Http\Client\HttpClientException;
use Phantom\Support\Logger;
use Throwable;

/**
 * Live currency conversion through the Frankfurter API (https://frankfurter.dev).
 *
 *   GET {base_url}/latest?base=PHP&symbols=USD,EUR,JPY
 *   200 {"amount":1.0,"base":"PHP","date":"2026-11-04","rates":{"EUR":0.013971,"JPY":2.491,"USD":0.015741}}
 *
 * Resilience strategy (the API is free and key-less, but may be slow or down):
 *   1. in-memory cache for the current request
 *   2. exchange_rates table, considered fresh for `cache_minutes` (default 6h)
 *   3. live API call, results written back to the table
 *   4. stale rows from the table if the API fails
 *   5. built-in approximate rates as a last resort
 */
class CurrencyService
{
    /** Approximate rates (PHP -> X) used only if the API has never been reachable. */
    private const FALLBACK_RATES = [
        'USD' => 0.0172, 'EUR' => 0.0158, 'JPY' => 2.60, 'GBP' => 0.0133, 'KRW' => 23.5, 'SGD' => 0.0228,
    ];

    /** @var array<string, float>|null */
    private ?array $rates = null;

    private string $source = 'fallback';

    private ?string $rateDate = null;

    public function __construct(private HttpClient $http, private Logger $logger)
    {
    }

    public function base(): string
    {
        return (string) config('app.currency.base', 'PHP');
    }

    /** @return string[] */
    public function supported(): array
    {
        return (array) config('app.currency.supported', ['PHP']);
    }

    public function symbol(string $currency): string
    {
        return (string) (config('app.currency.symbols')[$currency] ?? $currency . ' ');
    }

    /** @return array<string, float> rates from the base currency */
    public function rates(): array
    {
        if ($this->rates !== null) {
            return $this->rates;
        }

        $targets = array_values(array_diff($this->supported(), [$this->base()]));
        $stored = $this->storedRates();
        $maxAge = (int) config('services.frankfurter.cache_minutes', 360) * 60;

        if ($stored['rates'] && $stored['age'] !== null && $stored['age'] < $maxAge && !array_diff($targets, array_keys($stored['rates']))) {
            return $this->remember($stored['rates'], 'cache', $stored['date']);
        }

        $live = $this->fetchLive($targets);
        if ($live !== null) {
            $this->storeRates($live['rates'], $live['date']);
            return $this->remember($live['rates'], 'live', $live['date']);
        }

        if ($stored['rates']) {
            return $this->remember($stored['rates'] + self::FALLBACK_RATES, 'stale', $stored['date']);
        }
        return $this->remember(self::FALLBACK_RATES, 'fallback', null);
    }

    public function rate(string $currency): float
    {
        if ($currency === $this->base()) {
            return 1.0;
        }
        return $this->rates()[$currency] ?? self::FALLBACK_RATES[$currency] ?? 1.0;
    }

    public function convert(float $amountInBase, string $currency): float
    {
        return $amountInBase * $this->rate($currency);
    }

    public function format(float $amountInBase, string $currency): string
    {
        $value = $this->convert($amountInBase, $currency);
        $decimals = in_array($currency, ['JPY', 'KRW'], true) ? 0 : 2;
        return $this->symbol($currency) . number_format($value, $decimals);
    }

    /** @return array{source: string, date: ?string, base: string} where the current rates came from */
    public function info(): array
    {
        $this->rates();
        return ['source' => $this->source, 'date' => $this->rateDate, 'base' => $this->base()];
    }

    /**
     * @param string[] $targets
     * @return array{rates: array<string, float>, date: string}|null
     */
    private function fetchLive(array $targets): ?array
    {
        $url = rtrim((string) config('services.frankfurter.base_url'), '/') . '/latest';
        try {
            $response = $this->http->timeout(6)->retry(1, 300)->get($url, [
                'base' => $this->base(),
                'symbols' => implode(',', $targets),
            ]);
        } catch (HttpClientException $e) {
            $this->logger->warning('Frankfurter unreachable', ['error' => $e->getMessage()]);
            return null;
        }

        $data = $response->json();
        if (!$response->ok() || !is_array($data) || !isset($data['rates']) || !is_array($data['rates'])) {
            $this->logger->warning('Frankfurter returned an unexpected response', ['status' => $response->status()]);
            return null;
        }

        $rates = [];
        foreach ($data['rates'] as $code => $rate) {
            if (is_string($code) && preg_match('/^[A-Z]{3}$/', $code) && is_numeric($rate) && $rate > 0) {
                $rates[$code] = (float) $rate;
            }
        }
        return $rates ? ['rates' => $rates, 'date' => (string) ($data['date'] ?? date('Y-m-d'))] : null;
    }

    /** @return array{rates: array<string, float>, age: ?int, date: ?string} */
    private function storedRates(): array
    {
        try {
            $rows = DB::table('exchange_rates')->where('base', $this->base())->get();
        } catch (Throwable) {
            return ['rates' => [], 'age' => null, 'date' => null];
        }
        $rates = [];
        $oldest = null;
        $date = null;
        foreach ($rows as $row) {
            $rates[$row['currency']] = (float) $row['rate'];
            $fetched = strtotime((string) $row['fetched_at']) ?: 0;
            $oldest = $oldest === null ? $fetched : min($oldest, $fetched);
            $date = $row['rate_date'];
        }
        return ['rates' => $rates, 'age' => $oldest === null ? null : time() - $oldest, 'date' => $date];
    }

    /** @param array<string, float> $rates */
    private function storeRates(array $rates, string $date): void
    {
        $now = date('Y-m-d H:i:s');
        try {
            foreach ($rates as $currency => $rate) {
                $values = ['rate' => $rate, 'rate_date' => $date, 'fetched_at' => $now, 'updated_at' => $now];
                $query = DB::table('exchange_rates')->where('base', $this->base())->where('currency', $currency);
                if ($query->exists()) {
                    $query->update($values);
                } else {
                    DB::table('exchange_rates')->insert($values + [
                        'base' => $this->base(), 'currency' => $currency, 'created_at' => $now,
                    ]);
                }
            }
        } catch (Throwable $e) {
            $this->logger->warning('Could not persist exchange rates', ['error' => $e->getMessage()]);
        }
    }

    /** @param array<string, float> $rates */
    private function remember(array $rates, string $source, ?string $date): array
    {
        $this->source = $source;
        $this->rateDate = $date;
        return $this->rates = $rates;
    }
}
