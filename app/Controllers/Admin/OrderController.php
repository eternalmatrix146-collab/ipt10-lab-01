<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Order;
use App\Services\CartException;
use App\Services\OrderService;
use Phantom\Database\DB;
use Phantom\Exceptions\HttpException;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $query = DB::table('orders')
            ->select('orders.*', 'users.name AS customer', 'users.email AS customer_email')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->latest('orders.created_at');
        if (in_array($status, Order::STATUSES, true)) {
            $query->where('orders.status', $status);
        }
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->whereGroup(fn ($q) => $q->where('orders.reference', 'LIKE', $like)->orWhere('users.name', 'LIKE', $like));
        }

        $counts = array_column(
            DB::table('orders')->select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')->get(),
            'total',
            'status'
        );

        return $this->view('admin.orders.index', [
            'page' => $query->paginate(15, max(1, (int) $request->query('page', 1))),
            'status' => $status,
            'q' => $search,
            'counts' => $counts,
        ]);
    }

    public function show(string $reference): Response
    {
        $order = Order::findByReference($reference) ?? throw new HttpException(404, 'Order not found.');
        return $this->view('admin.orders.show', ['order' => $order, 'customer' => $order->user()]);
    }

    public function status(string $reference, Request $request, OrderService $orders): Response
    {
        $order = Order::findByReference($reference) ?? throw new HttpException(404, 'Order not found.');
        $data = $this->validate($request, ['status' => 'required|in:' . implode(',', Order::STATUSES)]);
        try {
            $orders->changeStatus($order, $data['status']);
            $this->flash('success', "Order {$order->reference} is now " . strtoupper($data['status']) . '.');
        } catch (CartException $e) {
            $this->flash('error', $e->getMessage());
        }
        return $this->redirectRoute('admin.orders.show', ['reference' => $order->reference]);
    }
}
