<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Services\CartException;
use App\Services\OrderService;
use Phantom\Auth\Auth;
use Phantom\Exceptions\HttpException;
use Phantom\Http\Controller;
use Phantom\Http\Response;

class OrderController extends Controller
{
    public function __construct(private Auth $auth)
    {
    }

    public function index(): Response
    {
        return $this->view('orders.index', [
            'orders' => Order::where('user_id', $this->auth->id())->latest()->get(),
        ]);
    }

    public function show(string $reference): Response
    {
        return $this->view('orders.show', [
            'order' => $this->findOwned($reference),
            'callingCard' => (bool) session()->getFlash('calling_card', false),
        ]);
    }

    public function cancel(string $reference, OrderService $orders): Response
    {
        $order = $this->findOwned($reference);
        try {
            $orders->cancelByCustomer($order);
            $this->flash('success', "Order {$order->reference} was cancelled.");
        } catch (CartException $e) {
            $this->flash('error', $e->getMessage());
        }
        return $this->redirectRoute('orders.show', ['reference' => $order->reference]);
    }

    /** 404 (not 403) for other people's orders, so references can't be probed. */
    private function findOwned(string $reference): Order
    {
        $order = Order::findByReference($reference);
        if ($order === null || (int) $order->user_id !== $this->auth->id()) {
            throw new HttpException(404, 'Order not found.');
        }
        return $order;
    }
}
