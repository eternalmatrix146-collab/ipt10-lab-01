<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Product;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => (string) $request->query('category', ''),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'sort' => array_key_exists((string) $request->query('sort'), Product::SORTS) ? (string) $request->query('sort') : 'featured',
            'in_stock' => $request->query('in_stock') === '1',
        ];

        $page = Product::search($filters)->paginate(12, max(1, (int) $request->query('page', 1)));
        $categories = Category::ordered();
        $active = null;
        foreach ($categories as $category) {
            if ($category->slug === $filters['category']) {
                $active = $category;
            }
        }

        return $this->view('shop.index', [
            'page' => $page,
            'filters' => $filters,
            'categories' => $categories,
            'activeCategory' => $active,
        ]);
    }
}
