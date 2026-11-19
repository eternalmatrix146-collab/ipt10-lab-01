<?php

declare(strict_types=1);

namespace App\Models;

use Phantom\Database\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $motif  artwork motif used by ProductArt (mask, coat, gadget, book, cup, star)
 */
class Category extends Model
{
    protected static string $table = 'categories';

    protected static array $fillable = ['name', 'slug', 'description', 'motif'];

    public const MOTIFS = ['mask', 'coat', 'gadget', 'book', 'cup', 'star'];

    /** @return Category[] */
    public static function ordered(): array
    {
        return static::query()->orderBy('name')->get();
    }

    public function productCount(): int
    {
        return $this->hasMany(Product::class, 'category_id')->count();
    }
}
