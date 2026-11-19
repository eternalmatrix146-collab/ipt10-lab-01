<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * The agent loop (manual tool-calling loop over OpenAI-style Chat Completions):
 *
 *   1. append the user's message
 *   2. call Nemotron with the system prompt + tool definitions + history
 *   3. append the assistant message (text + tool_calls; reasoning text is dropped)
 *   4. if it asked for tools: run every call, append one `tool` message per
 *      call (matched by tool_call_id), and go back to 2
 *   5. otherwise return the text
 *
 * The stored history is only ever appended to — earlier turns are never edited.
 */
class NimAgent implements AgentInterface
{
    public function __construct(
        private NimClient $client,
        private ToolRegistry $tools,
        private string $systemPrompt,
        private int $maxSteps = 8
    ) {
    }

    public function run(array $history, string $userMessage, string $context = ''): AgentResult
    {
        $messages = $history;
        // Only the server writes <context>; strip look-alike tags typed by the user.
        $userMessage = (string) preg_replace('#</?\s*context\s*>#i', '', $userMessage);
        $text = $context !== '' ? "<context>{$context}</context>\n\n{$userMessage}" : $userMessage;
        $messages[] = ['role' => 'user', 'content' => $text];

        $actions = [];
        $cards = [];
        $effects = [];
        $inputTokens = 0;
        $outputTokens = 0;

        for ($step = 1; $step <= $this->maxSteps; $step++) {
            $response = $this->client->createChatCompletion([
                'messages' => array_merge([['role' => 'system', 'content' => $this->systemPrompt]], $messages),
                'tools' => $this->tools->definitions(),
                'tool_choice' => 'auto',
            ]);

            $inputTokens += (int) ($response['usage']['prompt_tokens'] ?? 0);
            $outputTokens += (int) ($response['usage']['completion_tokens'] ?? 0);

            $choice = is_array($response['choices'][0] ?? null) ? $response['choices'][0] : [];
            $finish = (string) ($choice['finish_reason'] ?? 'stop');
            $content = self::clean((string) ($choice['message']['content'] ?? ''));
            $toolCalls = self::toolCalls($choice['message']['tool_calls'] ?? []);

            if ($finish === 'content_filter') {
                $reply = "I can't help with that one. I'm here for shopping questions — products, your cart and your orders.";
                $messages[] = ['role' => 'assistant', 'content' => $reply];
                return new AgentResult($reply, $messages, $actions, $cards, $effects, $inputTokens, $outputTokens, 'refusal');
            }

            $assistant = ['role' => 'assistant', 'content' => $content];
            if ($toolCalls !== []) {
                $assistant['tool_calls'] = $toolCalls;
            }
            $messages[] = $assistant;

            if ($toolCalls === []) {
                $reply = $content;
                if ($finish === 'length') {
                    $reply .= ($reply === '' ? '' : "\n\n") . '(My reply was cut short — ask me to continue.)';
                }
                return new AgentResult($reply !== '' ? $reply : 'Done.', $messages, $actions, $cards, $effects, $inputTokens, $outputTokens, $finish);
            }

            // Run every tool the model asked for; each result is its own `tool` message.
            foreach ($toolCalls as $call) {
                $name = $call['function']['name'];
                $input = json_decode($call['function']['arguments'], true);
                $result = is_array($input)
                    ? $this->tools->execute($name, $input)
                    : ToolResult::error('The tool arguments were not valid JSON. Send a JSON object.');

                $messages[] = ['role' => 'tool', 'tool_call_id' => $call['id'], 'content' => $result->content()];
                $actions[] = [
                    'tool' => $name,
                    'label' => $this->tools->get($name)?->label ?: $name,
                    'summary' => $result->summary,
                    'error' => $result->isError,
                ];
                if ($result->cards) {
                    $cards = $result->cards; // show the most recent set of products
                }
                $effects = array_merge($effects, $result->effects);
            }
        }

        $reply = 'That took more steps than I am allowed in one go. Could you narrow the request down?';
        $messages[] = ['role' => 'assistant', 'content' => $reply];
        return new AgentResult($reply, $messages, $actions, $cards, $effects, $inputTokens, $outputTokens, 'max_steps');
    }

    /** Text of a one-shot completion (no tools). @param array<string, mixed> $response */
    public static function text(array $response): string
    {
        return self::clean((string) ($response['choices'][0]['message']['content'] ?? ''));
    }

    /** Remove reasoning that some deployments leave inline as <think>…</think>. */
    public static function clean(string $text): string
    {
        $text = (string) preg_replace('/<think>.*?<\/think>/s', '', $text);
        $end = strrpos($text, '</think>');
        if ($end !== false) {
            $text = substr($text, $end + strlen('</think>')); // opening tag was part of the prompt template
        }
        return trim($text);
    }

    /**
     * True if a stored history is in Chat Completions format. Conversations
     * saved by the old Claude integration (content block arrays) are not.
     *
     * @param array<int, mixed> $messages
     */
    public static function isCompatible(array $messages): bool
    {
        foreach ($messages as $message) {
            if (!is_array($message) || !in_array($message['role'] ?? '', ['user', 'assistant', 'tool'], true)
                || !(is_string($message['content'] ?? null) || ($message['content'] ?? null) === null)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Normalise tool calls into the exact shape we send back: string arguments,
     * a non-empty id, type "function".
     *
     * @return array<int, array{id: string, type: string, function: array{name: string, arguments: string}}>
     */
    private static function toolCalls(mixed $raw): array
    {
        $calls = [];
        foreach (is_array($raw) ? $raw : [] as $call) {
            $name = is_array($call) ? (string) ($call['function']['name'] ?? '') : '';
            if ($name === '') {
                continue;
            }
            $arguments = $call['function']['arguments'] ?? '{}';
            if (!is_string($arguments)) {
                $arguments = (string) json_encode($arguments === [] ? new \stdClass() : $arguments, JSON_UNESCAPED_UNICODE);
            }
            $calls[] = [
                'id' => (string) ($call['id'] ?? '') !== '' ? (string) $call['id'] : 'call_' . bin2hex(random_bytes(6)),
                'type' => 'function',
                'function' => ['name' => $name, 'arguments' => trim($arguments) === '' ? '{}' : $arguments],
            ];
        }
        return $calls;
    }
}
