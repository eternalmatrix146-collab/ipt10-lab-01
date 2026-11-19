<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Order;
use App\Models\Product;
use Phantom\Database\DB;

/**
 * Tools for "Rook Intel", the admin analytics agent. All tools are read-only.
 *
 * Guardrail: the agent can never change data. update_order_status and
 * restock_product only PREPARE a change — they validate it and return a link
 * to the normal admin page, where a human applies it through the regular
 * CSRF-protected form. Tool results contain customer-written text (customer
 * names), so a prompt-injected model must not be able to write anything.
 * Every call is still recorded in agent_actions.
 */
class AdminToolkit
{
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
        $days = ['type' => 'integer', 'description' => 'Look-back window in days (1-365, default 30).'];

        return [
            new Tool(
                'sales_overview',
                'Revenue, order count, average order value and status breakdown for the last N days, plus a per-day revenue series. Cancelled orders are excluded from revenue.',
                ['properties' => ['days' => $days]],
                fn (ToolInput $in) => $this->salesOverview($in->int('days', 30, 1, 365)),
                'Pulled sales overview'
            ),
            new Tool(
                'top_products',
                'Best-selling products by units sold over the last N days.',
                ['properties' => ['days' => $days, 'limit' => ['type' => 'integer', 'description' => '1-20, default 5']]],
                fn (ToolInput $in) => $this->topProducts($in->int('days', 30, 1, 365), $in->int('limit', 5, 1, 20)),
                'Ranked top products'
            ),
            new Tool(
                'low_stock_products',
                'Products whose stock is at or below a threshold, lowest first.',
                ['properties' => ['threshold' => ['type' => 'integer', 'description' => 'Default 5.']]],
                fn (ToolInput $in) => $this->lowStock($in->int('threshold', 5, 0, 1000)),
                'Checked inventory'
            ),
            new Tool(
                'recent_orders',
                'Most recent orders, optionally filtered by status.',
                [
                    'properties' => [
                        'status' => ['type' => 'string', 'enum' => Order::STATUSES],
                        'limit' => ['type' => 'integer', 'description' => '1-25, default 10'],
                    ],
                ],
                fn (ToolInput $in) => $this->recentOrders($in->string('status', '', 20), $in->int('limit', 10, 1, 25)),
                'Listed recent orders'
            ),
            new Tool(
                'category_performance',
                'Units sold and revenue per category over the last N days.',
                ['properties' => ['days' => $days]],
                fn (ToolInput $in) => $this->categoryPerformance($in->int('days', 30, 1, 365)),
                'Compared categories'
            ),
            new Tool(
                'customer_stats',
                'Customer counts: total customers, new customers in the last N days, and top spenders.',
                ['properties' => ['days' => $days]],
                fn (ToolInput $in) => $this->customerStats($in->int('days', 30, 1, 365)),
                'Analysed customers'
            ),
            new Tool(
                'update_order_status',
                'Prepare (do NOT apply) an order status change. Validates it and returns a link to the order page where the admin applies it. Nothing changes until the admin does.',
                [
                    'properties' => [
                        'reference' => ['type' => 'string'],
                        'status' => ['type' => 'string', 'enum' => Order::STATUSES],
                    ],
                    'required' => ['reference', 'status'],
                ],
                fn (ToolInput $in) => $this->updateOrderStatus($in),
                'Prepared a status change'
            ),
            new Tool(
                'restock_product',
                'Prepare (do NOT apply) a restock. Validates it and returns a link to the product edit page where the admin sets the new stock.',
                [
                    'properties' => [
                        'product_id' => ['type' => 'integer'],
                        'add_quantity' => ['type' => 'integer', 'description' => '1-500'],
                    ],
                    'required' => ['product_id', 'add_quantity'],
                ],
                fn (ToolInput $in) => $this->restock($in),
                'Prepared a restock'
            ),
        ];
    }

    private function since(int $days): string
    {
        return date('Y-m-d H:i:s', strtotime("-{$days} days"));
    }

    private function salesOverview(int $days): ToolResult
    {
        $since = $this->since($days);
        $valid = DB::table('orders')->where('created_at', '>=', $since)->where('status', '!=', 'cancelled');
        $revenue = (clone $valid)->sum('total');
        $count = (clone $valid)->count();

        $byStatus = DB::table('orders')->select('status')->selectRaw('COUNT(*) AS orders')
            ->where('created_at', '>=', $since)->groupBy('status')->get();

        $daily = DB::table('orders')->selectRaw('DATE(created_at) AS day, SUM(total) AS revenue, COUNT(*) AS orders')
            ->where('created_at', '>=', $since)->where('status', '!=', 'cancelled')
            ->groupBy('day')->orderBy('day')->get();

        return ToolResult::ok([
            'window_days' => $days,
            'revenue_php' => round($revenue, 2),
            'orders' => $count,
            'average_order_value_php' => $count > 0 ? round($revenue / $count, 2) : 0,
            'by_status' => $byStatus,
            'daily' => $daily,
        ], [], [], peso($revenue) . " over {$days}d");
    }

    private function topProducts(int $days, int $limit): ToolResult
    {
        $rows = DB::table('order_items')
            ->select('order_items.product_name')
            ->selectRaw('SUM(order_items.quantity) AS units, SUM(order_items.line_total) AS revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', $this->since($days))
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('order_items.product_name')
            ->orderBy('units', 'DESC')
            ->limit($limit)
            ->get();
        return ToolResult::ok(['window_days' => $days, 'products' => $rows], [], [], count($rows) . ' products');
    }

    private function lowStock(int $threshold): ToolResult
    {
        $rows = DB::table('products')->select('id', 'name', 'stock', 'price')
            ->where('stock', '<=', $threshold)->orderBy('stock')->limit(30)->get();
        return ToolResult::ok(['threshold' => $threshold, 'products' => $rows], [], [], count($rows) . " at ≤{$threshold}");
    }

    private function recentOrders(string $status, int $limit): ToolResult
    {
        $query = DB::table('orders')
            ->select('orders.reference', 'orders.status', 'orders.total', 'orders.payment_method', 'orders.created_at', 'users.name AS customer')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->latest('orders.created_at')
            ->limit($limit);
        if ($status !== '' && in_array($status, Order::STATUSES, true)) {
            $query->where('orders.status', $status);
        }
        $rows = $query->get();
        return ToolResult::ok(['orders' => $rows], [], [], count($rows) . ' orders');
    }

    private function categoryPerformance(int $days): ToolResult
    {
        $rows = DB::table('order_items')
            ->select('categories.name AS category')
            ->selectRaw('SUM(order_items.quantity) AS units, SUM(order_items.line_total) AS revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.created_at', '>=', $this->since($days))
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('categories.name')
            ->orderBy('revenue', 'DESC')
            ->get();
        return ToolResult::ok(['window_days' => $days, 'categories' => $rows], [], [], count($rows) . ' categories');
    }

    private function customerStats(int $days): ToolResult
    {
        $total = DB::table('users')->where('role', 'customer')->count();
        $new = DB::table('users')->where('role', 'customer')->where('created_at', '>=', $this->since($days))->count();
        $top = DB::table('orders')
            ->select('users.name')
            ->selectRaw('COUNT(orders.id) AS orders, SUM(orders.total) AS spent')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('users.id', 'users.name')
            ->orderBy('spent', 'DESC')
            ->limit(5)
            ->get();
        return ToolResult::ok(['total_customers' => $total, "new_last_{$days}_days" => $new, 'top_spenders' => $top], [], [], "{$total} customers");
    }

    private function updateOrderStatus(ToolInput $in): ToolResult
    {
        $order = Order::findByReference($in->required('reference'));
        if ($order === null) {
            throw new ToolError('No order with that reference.');
        }
        $status = $in->required('status');
        if (!in_array($status, Order::STATUSES, true)) {
            throw new ToolError('Unknown status. Use one of: ' . implode(', ', Order::STATUSES));
        }
        if ($order->status === 'cancelled' && $status !== 'cancelled') {
            throw new ToolError('A cancelled order cannot be reopened.');
        }
        // Guardrail: never write. The admin applies the change on the order page.
        $url = route('admin.orders.show', ['reference' => $order->reference]);
        return ToolResult::ok([
            'applied' => false,
            'reference' => $order->reference,
            'current_status' => $order->status,
            'proposed_status' => $status,
            'apply_url' => $url,
            'note' => 'Not applied. Give the admin this link; they confirm the change on the order page.',
        ], [], [], "{$order->reference}: {$order->status} → {$status} (awaiting admin)");
    }

    private function restock(ToolInput $in): ToolResult
    {
        $product = Product::find($in->int('product_id', 0));
        if ($product === null) {
            throw new ToolError('No product with that id.');
        }
        $add = $in->int('add_quantity', 0, 1, 500);
        // Guardrail: never write. The admin sets the stock on the product edit page.
        return ToolResult::ok([
            'applied' => false,
            'product' => $product->name,
            'current_stock' => (int) $product->stock,
            'proposed_add' => $add,
            'proposed_stock' => (int) $product->stock + $add,
            'apply_url' => route('admin.products.edit', ['id' => (int) $product->id]),
            'note' => 'Not applied. Give the admin this link; they update the stock on the product page.',
        ], [], [], "{$product->name} +{$add} (awaiting admin)");
    }
}
