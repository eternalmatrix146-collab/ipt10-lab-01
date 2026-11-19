<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Services\ProductArt;
use Phantom\Exceptions\HttpException;
use Phantom\Http\Controller;
use Phantom\Http\Response;

/**
 * Serves generated SVG artwork for products: /art/product/{id}.svg
 */
class ArtController extends Controller
{
    public function product(int $id, ProductArt $art): Response
    {
        $product = Product::findWithCategory($id) ?? throw new HttpException(404, 'No such product.');
        $svg = $art->render((int) $product->id, (string) $product->name, (string) ($product->motif ?? 'star'), !$product->inStock());

        return new Response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
