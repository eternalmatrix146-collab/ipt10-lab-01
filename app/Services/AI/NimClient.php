<?php

declare(strict_types=1);

namespace App\Services\AI;

use Phantom\Http\Client\HttpClient;
use Phantom\Http\Client\HttpClientException;
use Phantom\Http\Client\HttpResponse;

/**
 * Calls NVIDIA NIM's OpenAI-compatible Chat Completions API
 * (POST https://integrate.api.nvidia.com/v1/chat/completions) through the
 * framework's HttpClient.
 *
 * Request:
 *   headers  Authorization: Bearer nvapi-..., content-type: application/json
 *   body     {model, messages, tools, tool_choice, max_tokens, stream: false, reasoning_effort}
 * Response:
 *   {id, model, choices: [{message: {role, content, tool_calls?}, finish_reason: "stop"|"tool_calls"|"length"}],
 *    usage: {prompt_tokens, completion_tokens}}
 *
 * Rate limiting: up to three API keys (NVIDIA_API_KEY_1..3) are used in
 * rotation. Each call starts at a random key so traffic spreads evenly; a key
 * that answers 429 (or 401/402/403/5xx/network error) is skipped and the next
 * key is tried at once. Only when every key has failed is an AiException thrown.
 */
class NimClient
{
    /** Failures where another key (or a second try) can succeed. */
    private const TRANSIENT = ['rate_limited', 'overloaded', 'api_error', 'network'];

    /** Failures tied to one key: bad, disabled or out of credits. */
    private const KEY_SPECIFIC = ['authentication', 'permission', 'billing'];

    /**
     * @param array{api_keys?: string[], base_url?: string, status_url?: string, model?: string,
     *              reasoning_effort?: string, max_tokens?: int, timeout?: int} $config
     */
    public function __construct(private HttpClient $http, private array $config)
    {
    }

    /** @return string[] the configured, non-empty API keys */
    public function keys(): array
    {
        $keys = array_map(static fn ($k) => trim((string) $k), (array) ($this->config['api_keys'] ?? []));
        return array_values(array_filter($keys, static fn (string $k) => $k !== ''));
    }

    public function isConfigured(): bool
    {
        return $this->keys() !== [];
    }

    public function model(): string
    {
        return (string) ($this->config['model'] ?? 'nvidia/nemotron-3-ultra-550b-a55b');
    }

    /**
     * @param array<string, mixed> $request messages/tools (+ optional overrides)
     * @param int|null $keySlot force one key (0-based) — used by `phantom agent:ping`
     * @return array<string, mixed> decoded response body
     * @throws AiException
     */
    public function createChatCompletion(array $request, ?int $keySlot = null): array
    {
        $keys = $this->keys();
        if ($keys === []) {
            throw new AiException('No NVIDIA API key is set (NVIDIA_API_KEY_1..3).', 'not_configured');
        }
        if ($keySlot !== null) {
            if (!isset($keys[$keySlot])) {
                throw new AiException('NVIDIA_API_KEY_' . ($keySlot + 1) . ' is not set.', 'not_configured');
            }
            $keys = [$keys[$keySlot]];
        }

        $body = array_merge([
            'model' => $this->model(),
            'max_tokens' => (int) ($this->config['max_tokens'] ?? 8192),
            'stream' => false, // the NIM endpoint streams by default
        ], $request);
        $effort = trim((string) ($this->config['reasoning_effort'] ?? ''));
        if ($effort !== '' && !isset($body['reasoning_effort'])) {
            $body['reasoning_effort'] = $effort;
        }

        $url = rtrim((string) ($this->config['base_url'] ?? 'https://integrate.api.nvidia.com/v1'), '/') . '/chat/completions';
        $count = count($keys);
        $start = $count > 1 ? random_int(0, $count - 1) : 0;
        $attempts = max(2, $count); // a single key still gets one retry on transient errors
        $error = null;

        for ($i = 0; $i < $attempts; $i++) {
            $key = $keys[($start + $i) % $count];
            if ($i >= $count) {
                // Every key has been tried once; back off before re-using one.
                usleep(min(5, max(1, (int) ($error?->retryAfter() ?? $i))) * 1_000_000);
            }

            try {
                $response = $this->request($key)->postJson($url, $body);
                $response = $this->awaitPending($response, $key);
            } catch (HttpClientException $e) {
                $error = new AiException($e->getMessage(), 'network');
                continue;
            }

            $data = $response->json();
            if ($response->status() === 200 && is_array($data) && isset($data['choices'])) {
                return $data;
            }

            $error = $this->classify($response, $data);
            $rotate = in_array($error->kind(), self::TRANSIENT, true)
                || ($count > 1 && in_array($error->kind(), self::KEY_SPECIFIC, true) && $i + 1 < $count);
            if (!$rotate) {
                throw $error;
            }
        }

        throw $error;
    }

    private function request(string $key): HttpClient
    {
        return $this->http
            ->withHeaders(['Authorization' => 'Bearer ' . $key])
            ->timeout((int) ($this->config['timeout'] ?? 120))
            ->retry(0); // retries are handled above, by switching keys
    }

    /**
     * NVIDIA's cloud functions answer 202 + NVCF-REQID when a request is still
     * running; poll the status endpoint until the result is ready.
     */
    private function awaitPending(HttpResponse $response, string $key): HttpResponse
    {
        $statusUrl = rtrim((string) ($this->config['status_url'] ?? 'https://api.nvcf.nvidia.com/v2/nvcf/pexec/status'), '/');
        $deadline = time() + (int) ($this->config['timeout'] ?? 120);

        while ($response->status() === 202 && ($id = $response->header('nvcf-reqid')) !== null && time() < $deadline) {
            usleep(500_000);
            $response = $this->request($key)->get($statusUrl . '/' . rawurlencode($id));
        }
        return $response;
    }

    private function classify(HttpResponse $response, mixed $data): AiException
    {
        $status = $response->status();
        $message = 'Unknown error';
        if (is_array($data)) {
            $message = (string) ($data['error']['message'] ?? $data['detail'] ?? $data['title'] ?? $data['message']
                ?? (is_string($data['error'] ?? null) ? $data['error'] : 'Unknown error'));
        } elseif (trim($response->body()) !== '') {
            $message = mb_substr(trim($response->body()), 0, 200);
        }

        $kind = match (true) {
            $status === 402, str_contains(strtolower($message), 'insufficient credit') => 'billing',
            $status === 401 => 'authentication',
            $status === 403 => 'permission',
            $status === 404 => 'not_found',
            $status === 429 => 'rate_limited',
            $status === 202, $status === 503 => 'overloaded',
            $status === 400, $status === 413, $status === 422 => 'invalid_request',
            default => 'api_error',
        };

        $retryAfter = $response->header('retry-after');
        return new AiException(
            "NVIDIA NIM {$status}: {$message}",
            $kind,
            $status,
            is_numeric($retryAfter) ? (int) $retryAfter : null
        );
    }
}
