<?php

declare(strict_types=1);

namespace Phantom\Http\Client;

/**
 * cURL-based HTTP client for talking to external APIs.
 *
 * - JSON request/response helpers
 * - Automatic retries with exponential backoff on 429 / 5xx / network errors,
 *   honouring the Retry-After header
 * - A fake() hook so tests never touch the network
 *
 *   $res = $http->withHeaders(['x-api-key' => $key])->timeout(60)->postJson($url, $payload);
 *   if ($res->ok()) { $data = $res->json(); }
 */
class HttpClient
{
    /** @var (callable(string, string, array<string, string>, ?string): HttpResponse)|null */
    private static $fake = null;

    /** @var array<string, string> */
    private array $headers = ['Accept' => 'application/json', 'User-Agent' => 'PhantomPHP/1.0'];

    private int $timeout = 30;

    private int $retries = 2;

    private int $baseDelayMs = 500;

    /** Route every request to $responder instead of the network (tests). */
    public static function fake(?callable $responder): void
    {
        self::$fake = $responder;
    }

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): static
    {
        $clone = clone $this;
        $clone->headers = array_merge($this->headers, $headers);
        return $clone;
    }

    public function timeout(int $seconds): static
    {
        $clone = clone $this;
        $clone->timeout = $seconds;
        return $clone;
    }

    public function retry(int $times, int $baseDelayMs = 500): static
    {
        $clone = clone $this;
        $clone->retries = max(0, $times);
        $clone->baseDelayMs = max(0, $baseDelayMs);
        return $clone;
    }

    /** @param array<string, scalar> $query */
    public function get(string $url, array $query = []): HttpResponse
    {
        if ($query) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }
        return $this->send('GET', $url, null);
    }

    public function postJson(string $url, mixed $payload): HttpResponse
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            throw new HttpClientException('Could not encode request body as JSON: ' . json_last_error_msg());
        }
        return $this->withHeaders(['Content-Type' => 'application/json'])->send('POST', $url, $body);
    }

    public function send(string $method, string $url, ?string $body): HttpResponse
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                $response = $this->transport($method, $url, $body);
            } catch (HttpClientException $e) {
                if ($attempt > $this->retries) {
                    throw $e;
                }
                $this->sleep($attempt, null);
                continue;
            }

            $retryable = $response->status() === 429 || $response->status() >= 500;
            if (!$retryable || $attempt > $this->retries) {
                return $response;
            }
            $this->sleep($attempt, $response->header('retry-after'));
        }
    }

    protected function transport(string $method, string $url, ?string $body): HttpResponse
    {
        if (self::$fake !== null) {
            return (self::$fake)($method, $url, $this->headers, $body);
        }

        $handle = curl_init($url);
        $responseHeaders = [];
        $headerLines = [];
        foreach ($this->headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($line);
            },
        ]);
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        // Windows PHP builds (XAMPP) often ship without a CA bundle; use one if configured.
        $caBundle = getenv('CURL_CA_BUNDLE') ?: ($_ENV['CURL_CA_BUNDLE'] ?? '');
        if ($caBundle !== '' && is_file($caBundle)) {
            curl_setopt($handle, CURLOPT_CAINFO, $caBundle);
        }

        $raw = curl_exec($handle);
        if ($raw === false) {
            $error = curl_error($handle);
            curl_close($handle);
            throw new HttpClientException("Request to {$url} failed: {$error}");
        }
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return new HttpResponse($status, (string) $raw, $responseHeaders);
    }

    private function sleep(int $attempt, ?string $retryAfter): void
    {
        $ms = $retryAfter !== null && is_numeric($retryAfter)
            ? min(30_000, (int) ((float) $retryAfter * 1000))
            : $this->baseDelayMs * (2 ** ($attempt - 1));
        if ($ms > 0 && self::$fake === null) {
            usleep($ms * 1000);
        }
    }
}
