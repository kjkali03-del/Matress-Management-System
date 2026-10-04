<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationRun;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $orders = Order::query()->whereBetween('ordered_at', [$from, $to]);
        $paidOrders = (clone $orders)->where('payment_status', 'paid');

        $revenue = (float) (clone $paidOrders)->sum('total_amount');
        $estimatedCogs = (float) Order::query()
            ->whereBetween('ordered_at', [$from, $to])
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->selectRaw('COALESCE(SUM(orders.quantity * products.cost_price), 0) as cogs')
            ->value('cogs');

        $messages = Message::whereBetween('created_at', [$from, $to]);
        $newCustomers = Customer::whereBetween('created_at', [$from, $to])->count();

        $data = [
            'from' => $from,
            'to' => $to,
            'orders' => (clone $orders)->count(),
            'paid_orders' => (clone $paidOrders)->count(),
            'revenue' => $revenue,
            'cogs' => $estimatedCogs,
            'gross_profit' => $revenue - $estimatedCogs,
            'messages' => $messages->count(),
            'inbound_messages' => (clone $messages)->where('direction', 'inbound')->count(),
            'new_customers' => $newCustomers,
            'delivered_orders' => (clone $orders)->where('delivery_status', 'delivered')->count(),
            'active_products' => Product::where('is_active', true)->count(),
            'automation_runs' => AutomationRun::whereBetween('created_at', [$from, $to])->count(),
        ];

        $topProducts = Order::query()
            ->whereBetween('ordered_at', [$from, $to])
            ->selectRaw('product_name, SUM(quantity) as quantity, SUM(total_amount) as revenue')
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return view('admin.reports.index', compact('data', 'topProducts'));
    }
}
