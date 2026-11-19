<?php

declare(strict_types=1);

namespace App\Models;

use Phantom\Database\Model;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_token
 * @property int $product_id
 * @property int $quantity
 */
class CartItem extends Model
{
    protected static string $table = 'cart_items';

    protected static array $fillable = ['user_id', 'session_token', 'product_id', 'quantity'];
}
