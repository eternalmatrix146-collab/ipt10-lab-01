<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\AI\AgentService;
use Phantom\Database\DB;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

/**
 * Rook Intel console: the admin-side AI agent plus an audit log of every
 * tool call made by any agent.
 */
class IntelController extends Controller
{
    public function __construct(private AgentService $agent)
    {
    }

    public function index(Request $request): Response
    {
        $actions = DB::table('agent_actions')
            ->select('agent_actions.*', 'agent_conversations.channel', 'users.name AS user_name')
            ->join('agent_conversations', 'agent_conversations.id', '=', 'agent_actions.conversation_id')
            ->leftJoin('users', 'users.id', '=', 'agent_conversations.user_id')
            ->latest('agent_actions.id')
            ->limit(25)
            ->get();

        $usage = DB::connection()->selectOne(
            'SELECT COUNT(*) AS conversations, COALESCE(SUM(input_tokens), 0) AS input_tokens, COALESCE(SUM(output_tokens), 0) AS output_tokens FROM agent_conversations'
        );

        return $this->view('admin.intel', [
            'transcript' => $this->agent->transcript('admin'),
            'actions' => $actions,
            'usage' => $usage,
            'mode' => $this->agent->mode(),
            'model' => config('services.nvidia.model'),
            'ask' => (string) $request->query('ask', ''),
        ]);
    }

    public function chat(Request $request): Response
    {
        $data = $this->validate($request, ['message' => 'required|string|min:1|max:2000']);
        $result = $this->agent->chat('admin', $data['message']);
        return $this->json($result, $result['ok'] ? 200 : 503);
    }

    public function reset(): Response
    {
        $this->agent->reset('admin');
        return $this->json(['ok' => true]);
    }
}
