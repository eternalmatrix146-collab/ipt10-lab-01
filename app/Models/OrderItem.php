<?php

declare(strict_types=1);

namespace App\Models;

use Phantom\Database\Model;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property string $product_name
 * @property string $unit_price
 * @property int $quantity
 * @property string $line_total
 */
class OrderItem extends Model
{
    protected static string $table = 'order_items';

    protected static array $fillable = ['order_id', 'product_id', 'product_name', 'unit_price', 'quantity', 'line_total'];
}
