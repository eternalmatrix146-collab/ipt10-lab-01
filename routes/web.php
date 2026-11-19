<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
| $router is the Phantom\Routing\Router instance.
| Route middleware aliases: auth, admin, guest, throttle:max,seconds
*/

use App\Controllers\Admin;
use App\Controllers\AgentController;
use App\Controllers\Api\CatalogApiController;
use App\Controllers\ArtController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\CurrencyController;
use App\Controllers\HomeController;
use App\Controllers\OrderController;
use App\Controllers\ProductController;
use App\Controllers\ShopController;
use Phantom\Routing\Router;

/** @var Router $router */

// ---- Storefront ---------------------------------------------------------
$router->get('/', [HomeController::class, 'index'])->name('home');
$router->get('/shop', [ShopController::class, 'index'])->name('shop');
$router->get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
$router->post('/product/{slug}/reviews', [ProductController::class, 'review'])->middleware('auth')->name('product.review');
$router->get('/art/product/{id:\d+}.svg', [ArtController::class, 'product'])->name('art.product');
$router->post('/currency', [CurrencyController::class, 'update'])->name('currency.update');

// ---- Cart ---------------------------------------------------------------
$router->get('/cart', [CartController::class, 'index'])->name('cart');
$router->post('/cart/add', [CartController::class, 'add'])->name('cart.add');
$router->post('/cart/update', [CartController::class, 'update'])->name('cart.update');
$router->post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

// ---- Authentication -----------------------------------------------------
$router->group(['middleware' => ['guest']], function (Router $router): void {
    $router->get('/login', [AuthController::class, 'showLogin'])->name('login');
    $router->post('/login', [AuthController::class, 'login'])->middleware('throttle:10,60');
    $router->get('/register', [AuthController::class, 'showRegister'])->name('register');
    $router->post('/register', [AuthController::class, 'register'])->middleware('throttle:10,60');
});
$router->post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---- Customer area ------------------------------------------------------
$router->group(['middleware' => ['auth']], function (Router $router): void {
    $router->get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    $router->post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    $router->get('/orders', [OrderController::class, 'index'])->name('orders');
    $router->get('/orders/{reference}', [OrderController::class, 'show'])->name('orders.show');
    $router->post('/orders/{reference}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
});

// ---- JSON API -----------------------------------------------------------
$router->group(['prefix' => '/api'], function (Router $router): void {
    $router->get('/products', [CatalogApiController::class, 'products'])->name('api.products');
    $router->get('/products/{id:\d+}', [CatalogApiController::class, 'product'])->name('api.product');
    $router->get('/rates', [CatalogApiController::class, 'rates'])->name('api.rates');

    // Rook, the AI shopping agent
    $router->post('/agent/chat', [AgentController::class, 'chat'])->middleware('throttle:15,60')->name('agent.chat');
    $router->get('/agent/history', [AgentController::class, 'history'])->name('agent.history');
    $router->post('/agent/reset', [AgentController::class, 'reset'])->name('agent.reset');
});

// ---- Admin --------------------------------------------------------------
$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function (Router $router): void {
    $router->get('/', [Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    $router->get('/products', [Admin\ProductController::class, 'index'])->name('admin.products');
    $router->get('/products/create', [Admin\ProductController::class, 'create'])->name('admin.products.create');
    $router->post('/products', [Admin\ProductController::class, 'store'])->name('admin.products.store');
    $router->get('/products/{id:\d+}/edit', [Admin\ProductController::class, 'edit'])->name('admin.products.edit');
    $router->put('/products/{id:\d+}', [Admin\ProductController::class, 'update'])->name('admin.products.update');
    $router->delete('/products/{id:\d+}', [Admin\ProductController::class, 'destroy'])->name('admin.products.destroy');
    $router->post('/products/describe', [Admin\ProductController::class, 'describe'])
        ->middleware('throttle:10,60')->name('admin.products.describe');

    $router->get('/categories', [Admin\CategoryController::class, 'index'])->name('admin.categories');
    $router->post('/categories', [Admin\CategoryController::class, 'store'])->name('admin.categories.store');
    $router->put('/categories/{id:\d+}', [Admin\CategoryController::class, 'update'])->name('admin.categories.update');
    $router->delete('/categories/{id:\d+}', [Admin\CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    $router->get('/orders', [Admin\OrderController::class, 'index'])->name('admin.orders');
    $router->get('/orders/{reference}', [Admin\OrderController::class, 'show'])->name('admin.orders.show');
    $router->put('/orders/{reference}/status', [Admin\OrderController::class, 'status'])->name('admin.orders.status');

    $router->get('/customers', [Admin\CustomerController::class, 'index'])->name('admin.customers');

    $router->get('/intel', [Admin\IntelController::class, 'index'])->name('admin.intel');
    $router->post('/intel/chat', [Admin\IntelController::class, 'chat'])->middleware('throttle:15,60')->name('admin.intel.chat');
    $router->post('/intel/reset', [Admin\IntelController::class, 'reset'])->name('admin.intel.reset');
});
