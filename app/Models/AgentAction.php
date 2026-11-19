<?php

declare(strict_types=1);

namespace App\Models;

use Phantom\Database\Model;

/**
 * Audit log entry for one tool call made by an AI agent.
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $tool_name
 * @property string $input
 * @property string $output
 * @property int $is_error
 */
class AgentAction extends Model
{
    protected static string $table = 'agent_actions';

    protected static array $fillable = ['conversation_id', 'tool_name', 'input', 'output', 'is_error'];
}
