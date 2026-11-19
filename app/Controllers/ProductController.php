<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Models\Review;
use Phantom\Auth\Auth;
use Phantom\Database\DB;
use Phantom\Exceptions\HttpException;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

class ProductController extends Controller
{
    public function show(string $slug, Auth $auth): Response
    {
        $product = Product::findBySlugWithCategory($slug) ?? throw new HttpException(404, 'That product slipped away.');
        $reviews = Review::forProduct((int) $product->id);

        $hasReviewed = false;
        $hasPurchased = false;
        if ($auth->check()) {
            $hasReviewed = DB::table('reviews')->where('product_id', (int) $product->id)->where('user_id', $auth->id())->exists();
            $hasPurchased = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.user_id', $auth->id())
                ->where('order_items.product_id', (int) $product->id)
                ->where('orders.status', '!=', 'cancelled')
                ->exists();
        }

        return $this->view('shop.product', [
            'product' => $product,
            'rating' => $product->ratingSummary(),
            'reviews' => $reviews,
            'related' => $product->related(4),
            'hasReviewed' => $hasReviewed,
            'hasPurchased' => $hasPurchased,
        ]);
    }

    public function review(string $slug, Request $request, Auth $auth): Response
    {
        $product = Product::findBy('slug', $slug) ?? throw new HttpException(404, 'Product not found.');
        $data = $this->validate($request, [
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|min:5|max:1000',
        ]);

        $exists = DB::table('reviews')->where('product_id', (int) $product->id)->where('user_id', $auth->id())->exists();
        if ($exists) {
            $this->flash('error', 'You already reviewed this item.');
            return $this->back();
        }

        Review::create([
            'product_id' => (int) $product->id,
            'user_id' => $auth->id(),
            'rating' => (int) $data['rating'],
            'comment' => $data['comment'],
        ]);
        $this->flash('success', 'Review posted. Thanks for the intel!');
        return Response::redirect(route('product.show', ['slug' => $slug]) . '#reviews');
    }
}
