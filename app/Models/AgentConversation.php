<?php

declare(strict_types=1);

namespace App\Models;

use Phantom\Database\Model;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_token
 * @property string $channel      'shop' (Rook concierge) or 'admin' (Rook Intel)
 * @property string $messages     raw Chat Completions messages (JSON) — appended to, never rewritten
 * @property string $transcript   simplified chat log for the UI (JSON)
 * @property int $input_tokens
 * @property int $output_tokens
 */
class AgentConversation extends Model
{
    protected static string $table = 'agent_conversations';

    protected static array $fillable = [
        'user_id', 'session_token', 'channel', 'messages', 'transcript', 'input_tokens', 'output_tokens',
    ];

    /** @return array<int, array<string, mixed>> */
    public function apiMessages(): array
    {
        return json_decode((string) $this->messages, true) ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function transcriptEntries(): array
    {
        return json_decode((string) $this->transcript, true) ?: [];
    }
}
