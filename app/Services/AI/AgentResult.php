<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * The outcome of one user turn: the reply, what the agent did along the way,
 * and the updated raw message history to persist.
 */
final class AgentResult
{
    /**
     * @param array<int, array<string, mixed>> $messages updated Messages API history
     * @param array<int, array{tool: string, label: string, summary: string, error: bool}> $actions
     * @param array<int, array<string, mixed>> $cards product cards to show under the reply
     * @param array<string, mixed> $effects UI side-effects, e.g. ['cart_count' => 3]
     */
    public function __construct(
        public string $reply,
        public array $messages,
        public array $actions = [],
        public array $cards = [],
        public array $effects = [],
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public string $stopReason = 'end_turn',
        public string $mode = 'nim'
    ) {
    }
}
