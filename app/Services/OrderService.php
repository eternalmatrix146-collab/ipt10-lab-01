<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Phantom\Database\Connection;
use Phantom\Database\DB;

/**
 * Checkout and order lifecycle. Placing an order is one transaction:
 * re-check stock (row-locked), write the order + items, decrement stock,
 * empty the cart — either all of it happens or none of it does.
 */
class OrderService
{
    public function __construct(private CartService $cart, private CurrencyService $currency)
    {
    }

    /**
     * @param array{shipping_name: string, shipping_phone: string, shipping_address: string,
     *              shipping_city: string, payment_method: string, notes?: ?string} $details
     */
    public function placeOrder(User $user, array $details): Order
    {
        $items = $this->cart->items();
        if ($items === []) {
            throw new CartException('Your cart is empty.');
        }

        $displayCurrency = current_currency();
        $displayRate = $this->currency->rate($displayCurrency);

        return DB::transaction(function (Connection $db) use ($user, $details, $items, $displayCurrency, $displayRate): Order {
            $lock = $db->driver() === 'mysql' ? ' FOR UPDATE' : '';
            $subtotal = 0.0;
            $lines = [];

            foreach ($items as $item) {
                $product = $db->selectOne('SELECT id, name, price, stock FROM products WHERE id = ?' . $lock, [(int) $item['product']->id]);
                if ($product === null) {
                    throw new CartException('A product in your cart is no longer available.');
                }
                if ((int) $product['stock'] < $item['quantity']) {
                    throw new CartException("Only {$product['stock']} of {$product['name']} left — please update your cart.");
                }
                $lineTotal = round((float) $product['price'] * $item['quantity'], 2);
                $subtotal += $lineTotal;
                $lines[] = ['product' => $product, 'quantity' => $item['quantity'], 'line_total' => $lineTotal];
            }

            $subtotal = round($subtotal, 2);
            $shipping = $this->cart->shippingFee($subtotal);

            $order = Order::create([
                'user_id' => (int) $user->id,
                'reference' => $this->newReference(),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'total' => round($subtotal + $shipping, 2),
                'display_currency' => $displayCurrency,
                'display_rate' => $displayRate,
                'payment_method' => $details['payment_method'],
                'shipping_name' => $details['shipping_name'],
                'shipping_phone' => $details['shipping_phone'],
                'shipping_address' => $details['shipping_address'],
                'shipping_city' => $details['shipping_city'],
                'notes' => $details['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                OrderItem::create([
                    'order_id' => (int) $order->id,
                    'product_id' => (int) $line['product']['id'],
                    'product_name' => $line['product']['name'],
                    'unit_price' => (float) $line['product']['price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
                DB::table('products')->where('id', (int) $line['product']['id'])->decrement('stock', $line['quantity']);
            }

            $this->cart->clear();
            return $order;
        });
    }

    /** Customers may cancel while the order is still pending; stock is restored. */
    public function cancelByCustomer(Order $order): void
    {
        if ($order->status !== 'pending') {
            throw new CartException('Only pending orders can be cancelled.');
        }
        $this->changeStatus($order, 'cancelled');
    }

    public function changeStatus(Order $order, string $status): void
    {
        if (!in_array($status, Order::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid status [{$status}].");
        }
        if ($order->status === 'cancelled' && $status !== 'cancelled') {
            throw new CartException('A cancelled order cannot be reopened.');
        }

        DB::transaction(function () use ($order, $status): void {
            if ($status === 'cancelled' && $order->status !== 'cancelled') {
                foreach ($order->items() as $item) {
                    if ($item->product_id !== null) {
                        DB::table('products')->where('id', (int) $item->product_id)->increment('stock', (int) $item->quantity);
                    }
                }
            }
            $order->status = $status;
            $order->save();
        });
    }

    private function newReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $suffix = '';
            for ($i = 0; $i < 5; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'MM-' . date('ymd') . '-' . $suffix;
        } while (Order::findByReference($reference) !== null);
        return $reference;
    }
}
