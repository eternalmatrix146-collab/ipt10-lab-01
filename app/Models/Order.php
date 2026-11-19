<?php

declare(strict_types=1);

namespace App\Models;

use Phantom\Database\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $reference
 * @property string $status
 * @property string $subtotal
 * @property string $shipping_fee
 * @property string $total
 * @property string $payment_method
 */
class Order extends Model
{
    protected static string $table = 'orders';

    protected static array $fillable = [
        'user_id', 'reference', 'status', 'subtotal', 'shipping_fee', 'total',
        'display_currency', 'display_rate', 'payment_method',
        'shipping_name', 'shipping_phone', 'shipping_address', 'shipping_city', 'notes',
    ];

    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    public const PAYMENT_METHODS = [
        'cod' => 'Cash on delivery',
        'gcash' => 'GCash (simulated)',
        'card' => 'Card (simulated)',
    ];

    public static function findByReference(string $reference): ?self
    {
        return static::findBy('reference', strtoupper(trim($reference)));
    }

    /** @return OrderItem[] */
    public function items(): array
    {
        return $this->hasMany(OrderItem::class, 'order_id')->get();
    }

    public function user(): ?User
    {
        /** @var User|null */
        return $this->belongsTo(User::class, 'user_id');
    }

    public function itemCount(): int
    {
        return (int) array_sum(array_map(static fn (OrderItem $i) => (int) $i->quantity, $this->items()));
    }

    public function statusStep(): int
    {
        return $this->status === 'cancelled' ? -1 : (int) array_search($this->status, self::STATUSES, true);
    }

    public function paymentLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? (string) $this->payment_method;
    }
}
