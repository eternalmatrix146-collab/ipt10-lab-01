<?php

declare(strict_types=1);

namespace App\Models;

use Phantom\Database\DB;
use Phantom\Database\Model;
use Phantom\Database\QueryBuilder;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $tagline
 * @property string|null $description
 * @property string $price
 * @property int $stock
 * @property int $is_featured
 */
class Product extends Model
{
    protected static string $table = 'products';

    protected static array $fillable = ['category_id', 'name', 'slug', 'tagline', 'description', 'price', 'stock', 'is_featured'];

    public const SORTS = [
        'featured' => ['products.is_featured', 'DESC'],
        'newest' => ['products.created_at', 'DESC'],
        'price_asc' => ['products.price', 'ASC'],
        'price_desc' => ['products.price', 'DESC'],
        'name' => ['products.name', 'ASC'],
    ];

    /**
     * Catalog search shared by the shop page, the JSON API and the AI agent's search tool.
     *
     * @param array{q?: ?string, category?: ?string, min_price?: mixed, max_price?: mixed, in_stock?: bool, sort?: ?string} $filters
     */
    public static function search(array $filters): QueryBuilder
    {
        $query = DB::table('products')
            ->select('products.*', 'categories.name AS category_name', 'categories.slug AS category_slug', 'categories.motif AS motif')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->setModel(static::class);

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            foreach (preg_split('/\s+/', mb_substr($term, 0, 80)) ?: [] as $word) {
                $like = '%' . addcslashes($word, '%_\\') . '%';
                $query->whereGroup(fn (QueryBuilder $q) => $q
                    ->where('products.name', 'LIKE', $like)
                    ->orWhere('products.tagline', 'LIKE', $like)
                    ->orWhere('products.description', 'LIKE', $like)
                    ->orWhere('categories.name', 'LIKE', $like));
            }
        }
        if (!empty($filters['category'])) {
            $query->where('categories.slug', (string) $filters['category']);
        }
        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->where('products.price', '>=', (float) $filters['min_price']);
        }
        if (isset($filters['max_price']) && is_numeric($filters['max_price']) && (float) $filters['max_price'] > 0) {
            $query->where('products.price', '<=', (float) $filters['max_price']);
        }
        if (!empty($filters['in_stock'])) {
            $query->where('products.stock', '>', 0);
        }

        [$column, $direction] = self::SORTS[$filters['sort'] ?? 'featured'] ?? self::SORTS['featured'];
        $query->orderBy($column, $direction)->orderBy('products.id', 'DESC');

        return $query;
    }

    public static function findBySlugWithCategory(string $slug): ?self
    {
        return static::search([])->where('products.slug', $slug)->first();
    }

    public static function findWithCategory(int $id): ?self
    {
        return static::search([])->where('products.id', $id)->first();
    }

    public function category(): ?Category
    {
        /** @var Category|null */
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function inStock(): bool
    {
        return (int) $this->stock > 0;
    }

    public function stockLabel(): string
    {
        $stock = (int) $this->stock;
        return match (true) {
            $stock === 0 => 'Sold out',
            $stock <= 5 => "Only {$stock} left",
            default => 'In stock',
        };
    }

    /** @return array{average: float, count: int} */
    public function ratingSummary(): array
    {
        $row = DB::connection()->selectOne(
            'SELECT AVG(rating) AS average, COUNT(*) AS total FROM reviews WHERE product_id = ?',
            [(int) $this->id]
        );
        return ['average' => round((float) ($row['average'] ?? 0), 1), 'count' => (int) ($row['total'] ?? 0)];
    }

    /** @return Product[] same-category products, excluding this one */
    public function related(int $limit = 4): array
    {
        return static::search(['category' => $this->category_slug ?? $this->category()?->slug])
            ->where('products.id', '!=', (int) $this->id)
            ->limit($limit)
            ->get();
    }

    public function imageUrl(): string
    {
        return url('/art/product/' . (int) $this->id . '.svg');
    }

    /**
     * Compact representation for the AI agent's tool results.
     *
     * @return array<string, mixed>
     */
    public function toAgentArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category_name ?? $this->category()?->name,
            'tagline' => $this->tagline,
            'price_php' => (float) $this->price,
            'price_display' => money((float) $this->price),
            'stock' => (int) $this->stock,
            'url' => route('product.show', ['slug' => $this->slug]),
        ];
    }
}
