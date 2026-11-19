<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AI\AgentService;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

/**
 * JSON endpoints for the Rook chat widget.
 */
class AgentController extends Controller
{
    public function __construct(private AgentService $agent)
    {
    }

    public function chat(Request $request): Response
    {
        $data = $this->validate($request, [
            'message' => 'required|string|min:1|max:1000',
            'page' => 'nullable|string|max:200',
        ]);

        $result = $this->agent->chat('shop', $data['message'], (string) ($data['page'] ?? ''));
        return $this->json($result, $result['ok'] ? 200 : 503);
    }

    public function history(): Response
    {
        return $this->json([
            'mode' => $this->agent->mode(),
            'transcript' => $this->agent->transcript('shop'),
            'cart_count' => cart_count(),
        ]);
    }

    public function reset(): Response
    {
        $this->agent->reset('shop');
        return $this->json(['ok' => true]);
    }
}
