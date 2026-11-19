<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CurrencyService;
use Phantom\Auth\Auth;

/**
 * Tools for "Rook", the shopper-facing concierge. Each tool wraps an
 * existing service, so the agent goes through exactly the same business
 * rules (stock limits, ownership checks) as the regular web UI.
 *
 * Deliberately NOT provided: placing orders or taking payment. The agent
 * fills the cart; the human confirms checkout.
 */
class ShopToolkit
{
    public function __construct(
        private CartService $cart,
        private CurrencyService $currency,
        private Auth $auth,
        private KnowledgeBase $knowledge
    ) {
    }

    public function registry(): ToolRegistry
    {
        $registry = new ToolRegistry();
        foreach ($this->tools() as $tool) {
            $registry->register($tool);
        }
        return $registry;
    }

    /** @return Tool[] */
    public function tools(): array
    {
        return [
            new Tool(
                'search_products',
                'Search the Midnight Market catalog. Use for any question about what products exist, prices, or recommendations. '
                . 'Matches words in product names, taglines, descriptions and category names. Prices are in PHP (Philippine peso); '
                . 'price_display is already converted to the shopper\'s currency. Returns at most `limit` products.',
                [
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Keywords, e.g. "red mask" or "coffee". Empty string to browse.'],
                        'category' => ['type' => 'string', 'description' => 'Optional category slug from list_categories.'],
                        'max_price' => ['type' => 'number', 'description' => 'Optional maximum price in PHP.'],
                        'min_price' => ['type' => 'number', 'description' => 'Optional minimum price in PHP.'],
                        'sort' => ['type' => 'string', 'enum' => array_keys(Product::SORTS), 'description' => 'Sort order (default featured).'],
                        'in_stock_only' => ['type' => 'boolean', 'description' => 'Only return products that are in stock.'],
                        'limit' => ['type' => 'integer', 'description' => 'Max results, 1-8 (default 5).'],
                    ],
                    'required' => ['query'],
                ],
                fn (ToolInput $in) => $this->searchProducts($in),
                'Searched the catalog'
            ),
            new Tool(
                'get_product_details',
                'Get full details for one product (description, stock, rating, related items) by its numeric id or slug.',
                [
                    'properties' => [
                        'product_id' => ['type' => 'integer', 'description' => 'Product id from search results.'],
                        'slug' => ['type' => 'string', 'description' => 'Product slug, if the id is unknown.'],
                    ],
                ],
                fn (ToolInput $in) => $this->productDetails($in),
                'Inspected a product'
            ),
            new Tool(
                'list_categories',
                'List all product categories with their slugs and product counts.',
                ['properties' => []],
                fn () => $this->listCategories(),
                'Listed categories'
            ),
            new Tool(
                'add_to_cart',
                'Add a product to the shopper\'s cart (or increase its quantity). Only do this when the shopper clearly asked '
                . 'to add/buy something. Quantity is capped by stock and at 10 per item.',
                [
                    'properties' => [
                        'product_id' => ['type' => 'integer', 'description' => 'Product id.'],
                        'quantity' => ['type' => 'integer', 'description' => 'How many to add (default 1).'],
                    ],
                    'required' => ['product_id'],
                ],
                fn (ToolInput $in) => $this->addToCart($in),
                'Added to cart'
            ),
            new Tool(
                'view_cart',
                'Show what is currently in the shopper\'s cart with subtotal, shipping fee and total.',
                ['properties' => []],
                fn () => $this->viewCart(),
                'Checked the cart'
            ),
            new Tool(
                'update_cart_item',
                'Change the quantity of a product already in the cart. Quantity 0 removes it.',
                [
                    'properties' => [
                        'product_id' => ['type' => 'integer'],
                        'quantity' => ['type' => 'integer', 'description' => 'New quantity, 0 to remove.'],
                    ],
                    'required' => ['product_id', 'quantity'],
                ],
                fn (ToolInput $in) => $this->updateCartItem($in),
                'Updated the cart'
            ),
            new Tool(
                'list_my_orders',
                'List the logged-in shopper\'s recent orders with status and totals. Fails if the shopper is not logged in.',
                ['properties' => []],
                fn () => $this->listMyOrders(),
                'Looked up your orders'
            ),
            new Tool(
                'get_order_status',
                'Get the status, items and shipping details of one of the logged-in shopper\'s orders by reference (e.g. MM-261105-AB12C).',
                [
                    'properties' => ['reference' => ['type' => 'string', 'description' => 'Order reference.']],
                    'required' => ['reference'],
                ],
                fn (ToolInput $in) => $this->orderStatus($in),
                'Tracked an order'
            ),
            new Tool(
                'convert_price',
                'Convert an amount in PHP to another currency using live exchange rates from the Frankfurter API.',
                [
                    'properties' => [
                        'amount_php' => ['type' => 'number'],
                        'currency' => ['type' => 'string', 'enum' => $this->currency->supported()],
                    ],
                    'required' => ['amount_php', 'currency'],
                ],
                fn (ToolInput $in) => $this->convertPrice($in),
                'Converted currency'
            ),
            new Tool(
                'search_knowledge_base',
                'Search the Midnight Market help center: what the shop is and sells, its style and site vocabulary (heists, calling card, crew), '
                . 'who Rook is and how chats are stored, Rook Intel, who built the shop, shipping fees, delivery and tracking, payment methods, currencies, '
                . 'cart limits, stock, checkout, order references and statuses, cancelling, returns and refunds, changing orders, '
                . 'accounts and passwords, privacy, reviews, searching, and what Rook can do. Use it before answering any policy '
                . 'or how-to question, and answer only from what it returns.',
                [
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'The shopper\'s question or key words, e.g. "free shipping" or "cancel order".'],
                    ],
                    'required' => ['query'],
                ],
                fn (ToolInput $in) => $this->searchKnowledge($in),
                'Checked the help center'
            ),
        ];
    }

    private function searchProducts(ToolInput $in): ToolResult
    {
        $limit = $in->int('limit', 5, 1, 8);
        $products = Product::search([
            'q' => $in->string('query', '', 80),
            'category' => $in->string('category', '', 60),
            'min_price' => $in->float('min_price'),
            'max_price' => $in->float('max_price'),
            'in_stock' => $in->bool('in_stock_only'),
            'sort' => $in->string('sort', 'featured', 20),
        ])->limit($limit)->get();

        $list = array_map(static fn (Product $p) => $p->toAgentArray(), $products);
        $summary = count($list) . ' result' . (count($list) === 1 ? '' : 's') . ' for "' . $in->string('query', '', 40) . '"';
        return ToolResult::ok(
            ['count' => count($list), 'products' => $list, 'shopper_currency' => current_currency()],
            array_map(fn (Product $p) => $this->card($p), $products),
            [],
            $summary
        );
    }

    private function productDetails(ToolInput $in): ToolResult
    {
        $id = $in->int('product_id', 0);
        $product = $id > 0 ? Product::findWithCategory($id) : Product::findBySlugWithCategory($in->string('slug', '', 170));
        if ($product === null) {
            throw new ToolError('No product found with that id/slug. Use search_products first.');
        }
        $rating = $product->ratingSummary();
        $data = $product->toAgentArray() + [
            'description' => $product->description,
            'rating_average' => $rating['average'],
            'rating_count' => $rating['count'],
            'related' => array_map(static fn (Product $p) => ['id' => (int) $p->id, 'name' => $p->name, 'price_display' => money((float) $p->price)], $product->related(3)),
        ];
        return ToolResult::ok($data, [$this->card($product)], [], $product->name);
    }

    private function listCategories(): ToolResult
    {
        $data = array_map(static fn (Category $c) => [
            'slug' => $c->slug,
            'name' => $c->name,
            'description' => $c->description,
            'product_count' => $c->productCount(),
        ], Category::ordered());
        return ToolResult::ok(['categories' => $data], [], [], count($data) . ' categories');
    }

    private function addToCart(ToolInput $in): ToolResult
    {
        $added = $this->cart->add($in->int('product_id', 0), $in->int('quantity', 1, 1, 10));
        $summary = $this->cart->summary();
        return ToolResult::ok(
            [
                'added' => $added['product']->name,
                'quantity_in_cart' => $added['quantity'],
                'cart_items' => $summary['count'],
                'cart_total_display' => money($summary['total']),
                'checkout_url' => route('checkout'),
            ],
            [],
            ['cart_count' => $summary['count']],
            $added['product']->name . ' × ' . $added['quantity']
        );
    }

    private function viewCart(): ToolResult
    {
        $summary = $this->cart->summary();
        $items = array_map(static fn (array $i) => [
            'product_id' => (int) $i['product']->id,
            'name' => $i['product']->name,
            'quantity' => $i['quantity'],
            'line_total_display' => money($i['line_total']),
        ], $summary['items']);

        return ToolResult::ok([
            'items' => $items,
            'item_count' => $summary['count'],
            'subtotal_display' => money($summary['subtotal']),
            'shipping_display' => $summary['shipping'] > 0 ? money($summary['shipping']) : 'FREE',
            'free_shipping_threshold_display' => money((float) config('app.shipping.free_over')),
            'total_display' => money($summary['total']),
            'checkout_url' => route('checkout'),
        ], [], ['cart_count' => $summary['count']], $summary['count'] . ' item(s)');
    }

    private function updateCartItem(ToolInput $in): ToolResult
    {
        $this->cart->update($in->int('product_id', 0), $in->int('quantity', 0, 0, 10));
        return $this->viewCart();
    }

    private function listMyOrders(): ToolResult
    {
        $user = $this->auth->user();
        if ($user === null) {
            throw new ToolError('The shopper is not logged in. Ask them to log in to see their orders: ' . route('login'));
        }
        $orders = Order::where('user_id', (int) $user->id)->latest()->limit(10)->get();
        $data = array_map(static fn (Order $o) => [
            'reference' => $o->reference,
            'status' => $o->status,
            'total_php' => (float) $o->total,
            'placed_at' => $o->created_at,
            'url' => route('orders.show', ['reference' => $o->reference]),
        ], $orders);
        return ToolResult::ok(['orders' => $data], [], [], count($data) . ' order(s)');
    }

    private function orderStatus(ToolInput $in): ToolResult
    {
        $user = $this->auth->user();
        if ($user === null) {
            throw new ToolError('The shopper is not logged in. Ask them to log in first: ' . route('login'));
        }
        $order = Order::findByReference($in->required('reference'));
        // Ownership check: never reveal another customer's order.
        if ($order === null || (int) $order->user_id !== (int) $user->id) {
            throw new ToolError('No order with that reference was found on this account.');
        }
        return ToolResult::ok([
            'reference' => $order->reference,
            'status' => $order->status,
            'placed_at' => $order->created_at,
            'payment_method' => $order->paymentLabel(),
            'items' => array_map(static fn ($i) => ['name' => $i->product_name, 'quantity' => (int) $i->quantity], $order->items()),
            'total_php' => (float) $order->total,
            'ship_to_city' => $order->shipping_city,
            'url' => route('orders.show', ['reference' => $order->reference]),
        ], [], [], $order->reference . ' is ' . $order->status);
    }

    private function convertPrice(ToolInput $in): ToolResult
    {
        $currency = strtoupper($in->required('currency'));
        if (!in_array($currency, $this->currency->supported(), true)) {
            throw new ToolError('Unsupported currency. Supported: ' . implode(', ', $this->currency->supported()));
        }
        $amount = (float) $in->float('amount_php', 0.0);
        $info = $this->currency->info();
        return ToolResult::ok([
            'amount_php' => $amount,
            'converted' => round($this->currency->convert($amount, $currency), 2),
            'formatted' => $this->currency->format($amount, $currency),
            'rate' => $this->currency->rate($currency),
            'rate_date' => $info['date'],
            'rate_source' => $info['source'],
        ], [], [], peso($amount) . ' → ' . $this->currency->format($amount, $currency));
    }

    private function searchKnowledge(ToolInput $in): ToolResult
    {
        $matches = $this->knowledge->search($in->required('query'), 3);
        if ($matches === []) {
            return ToolResult::ok([
                'matches' => [],
                'note' => 'No help-center entry matches. Say you are not sure rather than guessing, and suggest asking the shop team.',
                'topics' => $this->knowledge->topics(),
            ], [], [], 'No help article found');
        }
        return ToolResult::ok(['matches' => $matches], [], [], $matches[0]['topic']);
    }

    /** @return array<string, mixed> */
    private function card(Product $p): array
    {
        return [
            'id' => (int) $p->id,
            'name' => $p->name,
            'price' => money((float) $p->price),
            'image' => $p->imageUrl(),
            'url' => route('product.show', ['slug' => $p->slug]),
            'in_stock' => $p->inStock(),
        ];
    }
}
