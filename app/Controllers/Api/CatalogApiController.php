<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Product;
use App\Services\CurrencyService;
use Phantom\Exceptions\HttpException;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

/**
 * Public read-only JSON API:
 *   GET /api/products?q=&category=&max_price=&sort=&page=
 *   GET /api/products/{id}
 *   GET /api/rates
 */
class CatalogApiController extends Controller
{
    public function products(Request $request): Response
    {
        $page = Product::search([
            'q' => (string) $request->query('q', ''),
            'category' => (string) $request->query('category', ''),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'sort' => (string) $request->query('sort', 'featured'),
        ])->paginate(min(50, max(1, (int) $request->query('per_page', 12))), max(1, (int) $request->query('page', 1)));

        return $this->json([
            'data' => array_map(static fn (Product $p) => $p->toAgentArray() + ['image' => $p->imageUrl()], $page['data']),
            'meta' => [
                'total' => $page['total'],
                'page' => $page['page'],
                'per_page' => $page['per_page'],
                'pages' => $page['pages'],
                'currency' => current_currency(),
            ],
        ]);
    }

    public function product(int $id): Response
    {
        $product = Product::findWithCategory($id) ?? throw new HttpException(404, 'Product not found.');
        return $this->json(['data' => $product->toAgentArray() + [
            'description' => $product->description,
            'image' => $product->imageUrl(),
            'rating' => $product->ratingSummary(),
        ]]);
    }

    public function rates(CurrencyService $currency): Response
    {
        return $this->json([
            'base' => $currency->base(),
            'rates' => $currency->rates(),
            'source' => $currency->info()['source'],
            'date' => $currency->info()['date'],
            'provider' => 'Frankfurter (frankfurter.dev) — European Central Bank reference rates',
        ]);
    }
}
