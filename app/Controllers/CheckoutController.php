<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Services\CartException;
use App\Services\CartService;
use App\Services\OrderService;
use Phantom\Auth\Auth;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private OrderService $orders, private Auth $auth)
    {
    }

    public function show(): Response
    {
        $summary = $this->cart->summary();
        if ($summary['count'] === 0) {
            $this->flash('info', 'Your bag is empty — find something worth taking first.');
            return $this->redirect('/shop');
        }

        $lastOrder = Order::where('user_id', $this->auth->id())->latest()->first();
        return $this->view('checkout.index', [
            'cart' => $summary,
            'paymentMethods' => Order::PAYMENT_METHODS,
            'lastOrder' => $lastOrder,
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'shipping_name' => 'required|string|min:2|max:120',
            'shipping_phone' => ['required', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'shipping_address' => 'required|string|min:5|max:255',
            'shipping_city' => 'required|string|min:2|max:100',
            'payment_method' => 'required|in:' . implode(',', array_keys(Order::PAYMENT_METHODS)),
            'notes' => 'nullable|string|max:500',
        ], ['shipping_phone.regex' => 'Enter a valid phone number.']);

        /** @var User $user */
        $user = $this->auth->user();
        try {
            $order = $this->orders->placeOrder($user, $data);
        } catch (CartException $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect('/cart');
        }

        session()->flash('calling_card', true);
        return $this->redirectRoute('orders.show', ['reference' => $order->reference]);
    }
}
