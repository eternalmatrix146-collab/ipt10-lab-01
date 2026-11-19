<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Product;
use Phantom\Http\Controller;
use Phantom\Http\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        return $this->view('home.index', [
            'featured' => Product::search(['sort' => 'featured', 'in_stock' => true])->where('products.is_featured', 1)->limit(6)->get(),
            'newest' => Product::search(['sort' => 'newest'])->limit(4)->get(),
            'categories' => Category::ordered(),
        ]);
    }
}
