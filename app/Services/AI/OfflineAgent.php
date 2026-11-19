<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Keyword-based fallback used when no NVIDIA API key is configured, so the
 * chat widget and tool pipeline can still be demonstrated. It plans with
 * simple rules instead of a language model, but executes the very same tools.
 */
class OfflineAgent implements AgentInterface
{
    private const STOPWORDS = [
        'a', 'an', 'the', 'i', 'me', 'my', 'want', 'need', 'looking', 'for', 'show', 'find', 'some', 'any',
        'do', 'you', 'have', 'please', 'can', 'could', 'get', 'is', 'are', 'there', 'with', 'something',
        'under', 'below', 'less', 'than', 'cheaper', 'php', 'pesos', 'peso', 'what', 'recommend', 'buy', 'add', 'to', 'cart',
    ];

    public function __construct(private ToolRegistry $tools, private string $channel = 'shop')
    {
    }

    public function run(array $history, string $userMessage, string $context = ''): AgentResult
    {
        $text = mb_strtolower(trim($userMessage));
        $plan = $this->channel === 'admin' ? $this->planAdmin($text) : $this->planShop($text);

        $actions = [];
        $cards = [];
        $effects = [];
        $lines = [];

        foreach ($plan['calls'] as [$tool, $input]) {
            if (!$this->tools->has($tool)) {
                continue;
            }
            $result = $this->tools->execute($tool, $input);
            $actions[] = [
                'tool' => $tool,
                'label' => $this->tools->get($tool)?->label ?: $tool,
                'summary' => $result->summary,
                'error' => $result->isError,
            ];
            $cards = $result->cards ?: $cards;
            $effects = array_merge($effects, $result->effects);
            $lines[] = $this->describe($tool, $result);

            // Chain: "add X to cart" = search, then add the top hit.
            if ($tool === 'search_products' && ($plan['then_add'] ?? false) && !$result->isError) {
                $first = $result->data['products'][0] ?? null;
                if ($first !== null) {
                    $add = $this->tools->execute('add_to_cart', ['product_id' => $first['id'], 'quantity' => $plan['quantity'] ?? 1]);
                    $actions[] = ['tool' => 'add_to_cart', 'label' => 'Added to cart', 'summary' => $add->summary, 'error' => $add->isError];
                    $effects = array_merge($effects, $add->effects);
                    $lines = [$this->describe('add_to_cart', $add)];
                }
            }
        }

        $reply = trim(($plan['intro'] ?? '') . "\n\n" . implode("\n\n", array_filter($lines)));
        $reply .= "\n\n_(Offline mode: add an NVIDIA API key to unlock full conversations.)_";

        return new AgentResult($reply, $history, $actions, $cards, $effects, 0, 0, 'end_turn', 'offline');
    }

    /** @return array{intro?: string, calls: array<int, array{0: string, 1: array<string, mixed>}>, then_add?: bool, quantity?: int} */
    private function planShop(string $text): array
    {
        if (preg_match('/\b(MM-\d{6}-[A-Z0-9]{5})\b/i', $text, $m)) {
            return ['calls' => [['get_order_status', ['reference' => strtoupper($m[1])]]]];
        }
        // Questions about the shop, Rook itself or store policies go to the help center (knowledge base).
        if (preg_match('/\b(shipping|deliver(y|ies)?|payments?|pay|gcash|cod|refunds?|returns?|cancel\w*|accounts?|register|sign up|password|privacy|reviews?|currenc(y|ies)|exchange rate|polic(y|ies)|help|faq|who are you|(who|what) is rook|about (you|us|the shop|this shop|midnight market)|company|real store|who (made|built)|heists?|calling card|haul|crew|codename|hideout|what do you sell|chat history|theme)\b/', $text)
            && !preg_match('/\b(add|buy)\b/', $text)) {
            return ['calls' => [['search_knowledge_base', ['query' => $text]]]];
        }
        if (preg_match('/\b(my )?orders?\b/', $text) && !str_contains($text, 'add')) {
            return ['calls' => [['list_my_orders', []]]];
        }
        if (preg_match('/\b(cart|basket|bag)\b/', $text) && !preg_match('/\badd\b/', $text)) {
            return ['calls' => [['view_cart', []]]];
        }
        if (preg_match('/\bcategor/', $text)) {
            return ['calls' => [['list_categories', []]]];
        }
        if (preg_match('/^(hi|hello|hey|yo|good (morning|evening|afternoon))\b/', $text)) {
            return [
                'intro' => "Good evening. I'm Rook. Ask me to **find** something (\"masks under 800\"), **add** it to your cart, or **track** an order.",
                'calls' => [],
            ];
        }

        $input = ['query' => $this->keywords($text), 'limit' => 4];
        if (preg_match('/(?:under|below|less than|cheaper than|max)\s*(?:₱|php)?\s*(\d[\d,]*)/u', $text, $m)) {
            $input['max_price'] = (float) str_replace(',', '', $m[1]);
        }
        if (preg_match('/\bcheap(est)?\b/', $text)) {
            $input['sort'] = 'price_asc';
        }

        if (preg_match('/\b(add|buy)\b/', $text)) {
            $qty = preg_match('/\b(\d{1,2})\s*(x|pcs|pieces|of)?\b/', $text, $q) ? max(1, min(10, (int) $q[1])) : 1;
            return ['calls' => [['search_products', $input]], 'then_add' => true, 'quantity' => $qty];
        }

        return ['intro' => 'Here is what I found in the vault:', 'calls' => [['search_products', $input]]];
    }

    /** @return array{intro?: string, calls: array<int, array{0: string, 1: array<string, mixed>}>} */
    private function planAdmin(string $text): array
    {
        $calls = [];
        $days = preg_match('/(\d+)\s*days?/', $text, $m) ? (int) $m[1] : 30;
        if (preg_match('/stock|inventory|restock/', $text)) {
            $calls[] = ['low_stock_products', ['threshold' => 5]];
        }
        if (preg_match('/top|best|popular/', $text)) {
            $calls[] = ['top_products', ['days' => $days, 'limit' => 5]];
        }
        if (preg_match('/categor/', $text)) {
            $calls[] = ['category_performance', ['days' => $days]];
        }
        if (preg_match('/customer/', $text)) {
            $calls[] = ['customer_stats', ['days' => $days]];
        }
        if (preg_match('/recent|latest|pending|orders/', $text)) {
            $status = preg_match('/pending|processing|shipped|delivered|cancelled/', $text, $s) ? $s[0] : '';
            $calls[] = ['recent_orders', ['status' => $status, 'limit' => 5]];
        }
        if ($calls === [] || preg_match('/sales|revenue|overview|summary|brief/', $text)) {
            array_unshift($calls, ['sales_overview', ['days' => $days]]);
        }
        return ['intro' => 'Intel report:', 'calls' => $calls];
    }

    private function keywords(string $text): string
    {
        $text = (string) preg_replace('/(under|below|less than|cheaper than|max)\s*(?:₱|php)?\s*\d[\d,]*/u', ' ', $text);
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];
        $words = array_filter($words, fn ($w) => $w !== '' && !is_numeric($w) && !in_array($w, self::STOPWORDS, true));
        // Singularise naive plurals so "masks" matches "Mask".
        $words = array_map(static fn ($w) => strlen($w) > 3 && str_ends_with($w, 's') ? substr($w, 0, -1) : $w, $words);
        return implode(' ', array_slice(array_values($words), 0, 4));
    }

    private function describe(string $tool, ToolResult $result): string
    {
        if ($result->isError) {
            return '⚠ ' . ($result->data['error'] ?? 'Something went wrong.');
        }
        $d = $result->data;

        return match ($tool) {
            'search_products' => $d['count'] === 0
                ? 'Nothing matched. Try other words, or ask me to list the categories.'
                : implode("\n", array_map(static fn ($p) => "- **{$p['name']}** — {$p['price_display']}" . ($p['stock'] === 0 ? ' (sold out)' : ''), $d['products'])),
            'add_to_cart' => "Done — **{$d['added']}** is in your cart (×{$d['quantity_in_cart']}). Cart total: {$d['cart_total_display']}. [Go to checkout]({$d['checkout_url']})",
            'view_cart' => $d['item_count'] === 0
                ? 'Your cart is empty.'
                : implode("\n", array_map(static fn ($i) => "- {$i['name']} ×{$i['quantity']} — {$i['line_total_display']}", $d['items']))
                    . "\n\nSubtotal {$d['subtotal_display']} · Shipping {$d['shipping_display']} · **Total {$d['total_display']}**",
            'list_categories' => implode("\n", array_map(static fn ($c) => "- **{$c['name']}** ({$c['product_count']})", $d['categories'])),
            'list_my_orders' => $d['orders'] === []
                ? 'You have no orders yet.'
                : implode("\n", array_map(static fn ($o) => "- [{$o['reference']}]({$o['url']}) — " . strtoupper($o['status']) . ' — ' . peso($o['total_php']), $d['orders'])),
            'get_order_status' => "Order **{$d['reference']}** is **" . strtoupper($d['status']) . '** (placed ' . $d['placed_at'] . ', total ' . peso($d['total_php']) . ').',
            'sales_overview' => "**{$d['window_days']}-day sales:** " . peso($d['revenue_php']) . " from {$d['orders']} orders (avg " . peso($d['average_order_value_php']) . ').',
            'top_products' => "**Top products:**\n" . implode("\n", array_map(static fn ($p) => "- {$p['product_name']}: {$p['units']} sold", $d['products'])),
            'low_stock_products' => $d['products'] === []
                ? 'No products are low on stock.'
                : "**Low stock:**\n" . implode("\n", array_map(static fn ($p) => "- {$p['name']}: {$p['stock']} left", $d['products'])),
            'category_performance' => "**By category:**\n" . implode("\n", array_map(static fn ($c) => "- {$c['category']}: " . peso((float) $c['revenue']), $d['categories'])),
            'customer_stats' => "**Customers:** {$d['total_customers']} total.",
            'recent_orders' => "**Recent orders:**\n" . implode("\n", array_map(static fn ($o) => "- {$o['reference']} · {$o['customer']} · " . strtoupper($o['status']) . ' · ' . peso((float) $o['total']), $d['orders'])),
            'search_knowledge_base' => $d['matches'] === []
                ? "I couldn't find that in the help center. The shop team can help."
                : "**{$d['matches'][0]['topic']}**\n\n{$d['matches'][0]['answer']}",
            default => $result->summary,
        };
    }
}
