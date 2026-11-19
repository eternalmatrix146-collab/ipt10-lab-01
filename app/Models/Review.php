<?php

declare(strict_types=1);

namespace App\Models;

use Phantom\Database\DB;
use Phantom\Database\Model;

/**
 * @property int $id
 * @property int $product_id
 * @property int $user_id
 * @property int $rating
 * @property string $comment
 */
class Review extends Model
{
    protected static string $table = 'reviews';

    protected static array $fillable = ['product_id', 'user_id', 'rating', 'comment'];

    /** @return array<int, array<string, mixed>> reviews with the reviewer's name */
    public static function forProduct(int $productId): array
    {
        return DB::table('reviews')
            ->select('reviews.*', 'users.name AS user_name', 'users.codename AS user_codename')
            ->join('users', 'users.id', '=', 'reviews.user_id')
            ->where('reviews.product_id', $productId)
            ->latest('reviews.created_at')
            ->get();
    }
}
