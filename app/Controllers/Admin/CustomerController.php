<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Phantom\Database\DB;
use Phantom\Http\Controller;
use Phantom\Http\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $customers = DB::table('users')
            ->select('users.id', 'users.name', 'users.email', 'users.codename', 'users.role', 'users.created_at')
            ->selectRaw("(SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id AND orders.status <> 'cancelled') AS order_count")
            ->selectRaw("(SELECT COALESCE(SUM(total), 0) FROM orders WHERE orders.user_id = users.id AND orders.status <> 'cancelled') AS spent")
            ->orderBy('users.created_at', 'DESC')
            ->get();
        return $this->view('admin.customers', ['customers' => $customers]);
    }
}
