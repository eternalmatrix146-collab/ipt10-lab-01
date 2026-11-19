<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CartException;
use App\Services\CartService;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

/**
 * Cart pages and actions. Actions answer with JSON for fetch() calls and
 * with redirects + flash messages for plain form posts.
 */
class CartController extends Controller
{
    public function __construct(private CartService $cart)
    {
    }

    public function index(): Response
    {
        return $this->view('cart.index', ['cart' => $this->cart->summary()]);
    }

    public function add(Request $request): Response
    {
        $data = $this->validate($request, [
            'product_id' => 'required|integer',
            'quantity' => 'nullable|integer|between:1,10',
        ]);

        try {
            $added = $this->cart->add((int) $data['product_id'], (int) ($data['quantity'] ?? 1));
        } catch (CartException $e) {
            return $this->respond($request, false, $e->getMessage());
        }
        return $this->respond($request, true, "{$added['product']->name} is in your bag.");
    }

    public function update(Request $request): Response
    {
        $data = $this->validate($request, [
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|between:0,10',
        ]);
        try {
            $this->cart->update((int) $data['product_id'], (int) $data['quantity']);
        } catch (CartException $e) {
            return $this->respond($request, false, $e->getMessage());
        }
        return $this->respond($request, true, 'Cart updated.');
    }

    public function remove(Request $request): Response
    {
        $data = $this->validate($request, ['product_id' => 'required|integer']);
        $this->cart->remove((int) $data['product_id']);
        return $this->respond($request, true, 'Item removed.');
    }

    private function respond(Request $request, bool $ok, string $message): Response
    {
        if ($request->wantsJson()) {
            $summary = $this->cart->summary();
            return $this->json([
                'ok' => $ok,
                'message' => $message,
                'cart_count' => $summary['count'],
                'subtotal' => money($summary['subtotal']),
                'total' => money($summary['total']),
            ], $ok ? 200 : 422);
        }
        $this->flash($ok ? 'success' : 'error', $message);
        return $this->back('/cart');
    }
}
