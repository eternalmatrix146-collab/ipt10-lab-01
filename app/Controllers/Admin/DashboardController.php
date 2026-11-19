<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Phantom\Database\DB;
use Phantom\Http\Controller;
use Phantom\Http\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $since30 = date('Y-m-d H:i:s', strtotime('-30 days'));
        $valid = DB::table('orders')->where('status', '!=', 'cancelled');

        // Revenue for each of the last 14 days (zero-filled for the chart).
        $rows = DB::table('orders')
            ->selectRaw('DATE(created_at) AS day, SUM(total) AS revenue, COUNT(*) AS orders')
            ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime('-13 days')))
            ->where('status', '!=', 'cancelled')
            ->groupBy('day')
            ->get();
        $byDay = array_column($rows, null, 'day');
        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'day' => $day,
                'label' => date('M j', strtotime($day)),
                'revenue' => (float) ($byDay[$day]['revenue'] ?? 0),
                'orders' => (int) ($byDay[$day]['orders'] ?? 0),
            ];
        }

        return $this->view('admin.dashboard', [
            'stats' => [
                'revenue30' => (clone $valid)->where('created_at', '>=', $since30)->sum('total'),
                'orders30' => (clone $valid)->where('created_at', '>=', $since30)->count(),
                'pending' => DB::table('orders')->where('status', 'pending')->count(),
                'customers' => DB::table('users')->where('role', 'customer')->count(),
                'products' => DB::table('products')->count(),
                'lowStock' => DB::table('products')->where('stock', '<=', 5)->count(),
                'agentChats' => DB::table('agent_conversations')->count(),
                'agentActions' => DB::table('agent_actions')->count(),
            ],
            'series' => $series,
            'recentOrders' => DB::table('orders')
                ->select('orders.*', 'users.name AS customer')
                ->join('users', 'users.id', '=', 'orders.user_id')
                ->latest('orders.created_at')->limit(6)->get(),
            'lowStock' => DB::table('products')->where('stock', '<=', 5)->orderBy('stock')->limit(6)->get(),
        ]);
    }
}
