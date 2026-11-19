<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AgentAction;
use App\Models\AgentConversation;
use App\Services\CartService;
use Phantom\Auth\Auth;
use Phantom\Session\Session;
use Phantom\Support\Logger;

/**
 * Orchestrates a chat turn: loads the conversation, picks the Nemotron agent
 * (or the offline fallback), runs it, persists history + transcript +
 * token usage, and logs every tool call to agent_actions.
 */
class AgentService
{
    /** Start a fresh conversation once the raw history grows past this many messages. */
    private const MAX_HISTORY_MESSAGES = 80;

    public function __construct(
        private NimClient $client,
        private ShopToolkit $shopTools,
        private AdminToolkit $adminTools,
        private Session $session,
        private Auth $auth,
        private CartService $cart,
        private Logger $logger
    ) {
    }

    public function mode(): string
    {
        return $this->client->isConfigured() ? 'nim' : 'offline';
    }

    public function conversation(string $channel, bool $create = true): ?AgentConversation
    {
        $query = AgentConversation::where('channel', $channel)->latest('id');
        $userId = $this->auth->id();
        $userId !== null
            ? $query->where('user_id', $userId)
            : $query->where('session_token', $this->cart->sessionToken())->whereNull('user_id');

        $conversation = $query->first();
        if ($conversation === null && $create) {
            $conversation = AgentConversation::create([
                'user_id' => $userId,
                'session_token' => $userId === null ? $this->cart->sessionToken() : null,
                'channel' => $channel,
                'messages' => '[]',
                'transcript' => '[]',
            ]);
        }
        return $conversation;
    }

    /** @return array<int, array<string, mixed>> */
    public function transcript(string $channel): array
    {
        return $this->conversation($channel, false)?->transcriptEntries() ?? [];
    }

    public function reset(string $channel): void
    {
        $this->conversation($channel, false)?->delete();
    }

    /** @return array<string, mixed> JSON payload for the chat widget */
    public function chat(string $channel, string $message, string $page = ''): array
    {
        $conversation = $this->conversation($channel);
        if (count($conversation->apiMessages()) > self::MAX_HISTORY_MESSAGES) {
            $conversation->delete();
            $conversation = $this->conversation($channel);
        }

        $registry = $channel === 'admin' ? $this->adminTools->registry() : $this->shopTools->registry();
        $registry->onExecute(function (string $tool, array $input, ToolResult $result) use ($conversation): void {
            AgentAction::create([
                'conversation_id' => (int) $conversation->id,
                'tool_name' => $tool,
                'input' => json_encode($input === [] ? new \stdClass() : $input, JSON_UNESCAPED_UNICODE),
                'output' => mb_substr($result->content(), 0, 4000),
                'is_error' => $result->isError ? 1 : 0,
            ]);
        });

        $agent = $this->client->isConfigured()
            ? new NimAgent(
                $this->client,
                $registry,
                $channel === 'admin' ? Prompts::admin() : Prompts::shop(),
                (int) config('services.nvidia.max_agent_steps', 8)
            )
            : new OfflineAgent($registry, $channel);

        // Histories saved by the old Claude integration use another format: start the model's memory fresh.
        $history = $conversation->apiMessages();
        if (!NimAgent::isCompatible($history)) {
            $history = [];
        }

        try {
            $result = $agent->run($history, $message, $this->context($channel, $page));
        } catch (AiException $e) {
            $this->logger->error('Agent call failed', ['kind' => $e->kind(), 'error' => $e->getMessage()]);
            return [
                'ok' => false,
                'error' => $e->userMessage(),
                'mode' => $this->mode(),
                'cart_count' => $this->cart->count(),
            ];
        }

        $transcript = $conversation->transcriptEntries();
        $now = date('c');
        $transcript[] = ['role' => 'user', 'text' => $message, 'at' => $now];
        $transcript[] = [
            'role' => 'assistant',
            'text' => $result->reply,
            'actions' => $result->actions,
            'cards' => $result->cards,
            'at' => $now,
        ];

        $conversation->messages = json_encode($result->messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $conversation->transcript = json_encode($transcript, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $conversation->input_tokens = (int) $conversation->input_tokens + $result->inputTokens;
        $conversation->output_tokens = (int) $conversation->output_tokens + $result->outputTokens;
        $conversation->save();

        return [
            'ok' => true,
            'reply' => $result->reply,
            'actions' => $result->actions,
            'cards' => $result->cards,
            'mode' => $result->mode,
            'cart_count' => $result->effects['cart_count'] ?? $this->cart->count(),
            'usage' => ['input_tokens' => $result->inputTokens, 'output_tokens' => $result->outputTokens],
        ];
    }

    /**
     * One-shot generation (no tools): product copy for the admin product form.
     * Falls back to a template when no NVIDIA API key is configured.
     */
    public function describeProduct(string $name, string $category, string $tagline): array
    {
        if (!$this->client->isConfigured()) {
            $text = "{$name} slips out of the shadows and into your collection. "
                . ($tagline !== '' ? rtrim($tagline, '.') . '. ' : '')
                . "Built for late nights and bold moves, it pairs everyday practicality with a look that refuses to blend in. "
                . "Part of our {$category} line, it is made to be used, carried and shown off. Steal the spotlight.";
            return ['ok' => true, 'text' => $text, 'mode' => 'offline'];
        }

        try {
            $response = $this->client->createChatCompletion([
                'messages' => [
                    ['role' => 'system', 'content' => Prompts::productDescription()],
                    [
                        'role' => 'user',
                        'content' => "Product: {$name}\nCategory: {$category}\nTagline: " . ($tagline !== '' ? $tagline : '(none)'),
                    ],
                ],
                'reasoning_effort' => 'none', // short copywriting: answer directly
            ]);
        } catch (AiException $e) {
            $this->logger->error('Description generation failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'error' => $e->userMessage()];
        }

        $text = NimAgent::text($response);
        if (($response['choices'][0]['finish_reason'] ?? '') === 'content_filter' || $text === '') {
            return ['ok' => false, 'error' => 'The model did not return a description. Try again.'];
        }
        return ['ok' => true, 'text' => $text, 'mode' => 'nim'];
    }

    private function context(string $channel, string $page): string
    {
        $user = $this->auth->user();
        $parts = [
            'date: ' . date('l, F j, Y g:i A'),
            $user !== null ? 'logged_in: yes, name: ' . $user->name : 'logged_in: no',
        ];
        if ($channel === 'shop') {
            $parts[] = 'shopper_currency: ' . current_currency();
            $parts[] = 'cart_items: ' . $this->cart->count();
            if ($page !== '') {
                $parts[] = 'current_page: ' . mb_substr($page, 0, 120);
            }
        }
        return implode('; ', $parts);
    }
}
