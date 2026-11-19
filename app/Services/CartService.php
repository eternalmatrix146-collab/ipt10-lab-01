<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use Phantom\Auth\Auth;
use Phantom\Database\DB;
use Phantom\Database\QueryBuilder;
use Phantom\Session\Session;

/**
 * Database-backed cart. Guests are identified by a random token kept in
 * their session; on login the guest cart is merged into the user's cart.
 * Used by CartController and by the AI agent's cart tools.
 */
class CartService
{
    public const MAX_QTY_PER_ITEM = 10;

    public function __construct(private Session $session, private Auth $auth)
    {
    }

    public function sessionToken(): string
    {
        $token = $this->session->get('_cart_token');
        if (!is_string($token) || strlen($token) !== 32) {
            $token = bin2hex(random_bytes(16));
            $this->session->put('_cart_token', $token);
        }
        return $token;
    }

    private function scope(QueryBuilder $query, string $prefix = ''): QueryBuilder
    {
        $userId = $this->auth->id();
        return $userId !== null
            ? $query->where($prefix . 'user_id', $userId)
            : $query->where($prefix . 'session_token', $this->sessionToken());
    }

    /**
     * @return array<int, array{id: int, product: Product, quantity: int, line_total: float}>
     */
    public function items(): array
    {
        $rows = $this->scope(
            DB::table('cart_items')
                ->select('cart_items.id AS cart_item_id', 'cart_items.quantity AS cart_quantity', 'products.*', 'categories.name AS category_name', 'categories.slug AS category_slug', 'categories.motif AS motif')
                ->join('products', 'products.id', '=', 'cart_items.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id'),
            'cart_items.'
        )->orderBy('cart_items.id')->get();

        return array_map(static function (array $row): array {
            $quantity = (int) $row['cart_quantity'];
            $id = (int) $row['cart_item_id'];
            unset($row['cart_quantity'], $row['cart_item_id']);
            $product = Product::hydrate($row);
            return [
                'id' => $id,
                'product' => $product,
                'quantity' => $quantity,
                'line_total' => round((float) $product->price * $quantity, 2),
            ];
        }, $rows);
    }

    public function count(): int
    {
        return (int) $this->scope(DB::table('cart_items'))->sum('quantity');
    }

    public function subtotal(): float
    {
        return round(array_sum(array_column($this->items(), 'line_total')), 2);
    }

    public function shippingFee(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }
        return $subtotal >= (float) config('app.shipping.free_over', 3000) ? 0.0 : (float) config('app.shipping.flat_fee', 150);
    }

    /** @return array{items: array<int, array<string, mixed>>, count: int, subtotal: float, shipping: float, total: float} */
    public function summary(): array
    {
        $items = $this->items();
        $subtotal = round(array_sum(array_column($items, 'line_total')), 2);
        $shipping = $this->shippingFee($subtotal);
        return [
            'items' => $items,
            'count' => (int) array_sum(array_column($items, 'quantity')),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => round($subtotal + $shipping, 2),
        ];
    }

    /**
     * Adds a product (or increases its quantity). Quantity is capped by stock
     * and by MAX_QTY_PER_ITEM.
     *
     * @return array{product: Product, quantity: int}
     */
    public function add(int $productId, int $quantity = 1): array
    {
        if ($quantity < 1) {
            throw new CartException('Quantity must be at least 1.');
        }
        $product = Product::find($productId);
        if ($product === null) {
            throw new CartException('That product does not exist.');
        }
        if (!$product->inStock()) {
            throw new CartException("{$product->name} is sold out.");
        }

        $existing = $this->scope(CartItem::query())->where('product_id', $productId)->first();
        $current = $existing ? (int) $existing->quantity : 0;
        $limit = min((int) $product->stock, self::MAX_QTY_PER_ITEM);
        $newQuantity = min($current + $quantity, $limit);

        if ($newQuantity <= $current) {
            throw new CartException("You already have the maximum available quantity ({$limit}) of {$product->name} in your cart.");
        }

        if ($existing) {
            $existing->quantity = $newQuantity;
            $existing->save();
        } else {
            CartItem::create([
                'user_id' => $this->auth->id(),
                'session_token' => $this->auth->id() === null ? $this->sessionToken() : null,
                'product_id' => $productId,
                'quantity' => $newQuantity,
            ]);
        }

        return ['product' => $product, 'quantity' => $newQuantity];
    }

    public function update(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($productId);
            return;
        }
        $product = Product::find($productId);
        if ($product === null) {
            throw new CartException('That product does not exist.');
        }
        $quantity = min($quantity, (int) $product->stock, self::MAX_QTY_PER_ITEM);
        if ($quantity <= 0) {
            $this->remove($productId);
            throw new CartException("{$product->name} is sold out and was removed from your cart.");
        }
        $this->scope(DB::table('cart_items'))->where('product_id', $productId)->update([
            'quantity' => $quantity,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function remove(int $productId): bool
    {
        return $this->scope(DB::table('cart_items'))->where('product_id', $productId)->delete() > 0;
    }

    public function clear(): void
    {
        $this->scope(DB::table('cart_items'))->delete();
    }

    /** Called right after login: moves the guest cart into the user's cart. */
    public function mergeGuestCart(int $userId): void
    {
        $token = $this->session->get('_cart_token');
        if (!is_string($token)) {
            return;
        }
        $guestItems = DB::table('cart_items')->where('session_token', $token)->get();
        foreach ($guestItems as $item) {
            $existing = DB::table('cart_items')->where('user_id', $userId)->where('product_id', (int) $item['product_id'])->first();
            if ($existing) {
                DB::table('cart_items')->where('id', (int) $existing['id'])->update([
                    'quantity' => min(self::MAX_QTY_PER_ITEM, (int) $existing['quantity'] + (int) $item['quantity']),
                ]);
                DB::table('cart_items')->where('id', (int) $item['id'])->delete();
            } else {
                DB::table('cart_items')->where('id', (int) $item['id'])->update(['user_id' => $userId, 'session_token' => null]);
            }
        }
    }
}
