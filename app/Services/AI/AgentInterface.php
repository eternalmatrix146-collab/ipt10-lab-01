<?php

declare(strict_types=1);

namespace App\Services\AI;

interface AgentInterface
{
    /**
     * @param array<int, array<string, mixed>> $history previous Messages API messages
     * @param string $context per-turn facts (shopper name, currency...) prepended to the user's message
     */
    public function run(array $history, string $userMessage, string $context = ''): AgentResult;
}
