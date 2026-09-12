<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $paymentStatus = trim((string) $request->input('payment_status', ''));
        $deliveryStatus = trim((string) $request->input('delivery_status', ''));

        $orders = Order::query()
            ->with('customer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($orderQuery) use ($search) {
                    $orderQuery
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('product_size', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                in_array($status, [
                    'pending',
                    'confirmed',
                    'completed',
                    'cancelled',
                ], true),
                function ($query) use ($status) {
                    $query->where('status', $status);
                }
            )
            ->when(
                in_array($paymentStatus, [
                    'unpaid',
                    'partial',
                    'paid',
                ], true),
                function ($query) use ($paymentStatus) {
                    $query->where('payment_status', $paymentStatus);
                }
            )
            ->when(
                in_array($deliveryStatus, [
                    'pending',
                    'processing',
                    'delivered',
                    'cancelled',
                ], true),
                function ($query) use ($deliveryStatus) {
                    $query->where('delivery_status', $deliveryStatus);
                }
            )
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $summaryQuery = Order::query();

        $summary = [
            'total_orders' => (clone $summaryQuery)->count(),
            'pending_orders' => (clone $summaryQuery)
                ->where('status', 'pending')
                ->count(),
            'completed_orders' => (clone $summaryQuery)
                ->where('status', 'completed')
                ->count(),
            'total_sales' => (clone $summaryQuery)
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
        ];

        return view('admin.orders.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'paymentStatus' => $paymentStatus,
            'deliveryStatus' => $deliveryStatus,
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        return view('admin.orders.create', [
            'customers' => $customers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'product_size' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'status' => [
                'required',
                'in:pending,confirmed,completed,cancelled',
            ],
            'payment_status' => [
                'required',
                'in:unpaid,partial,paid',
            ],
            'delivery_status' => [
                'required',
                'in:pending,processing,delivered,cancelled',
            ],
            'delivery_address' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'ordered_at' => ['nullable', 'date'],
        ]);

        $validated['total_amount'] =
            (float) $validated['quantity'] *
            (float) $validated['unit_price'];

        $validated['order_number'] = $this->generateOrderNumber();

        Order::create($validated);

        return redirect()
            ->route('admin.orders.index')
            ->with('status', 'Order created successfully.');
    }

    public function show(Order $order): View
    {
        $order->load('customer');

        return view('admin.orders.show', [
            'order' => $order,
        ]);
    }

    public function edit(Order $order): View
    {
        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        return view('admin.orders.edit', [
            'order' => $order,
            'customers' => $customers,
        ]);
    }

    public function update(
        Request $request,
        Order $order
    ): RedirectResponse {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'product_size' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'status' => [
                'required',
                'in:pending,confirmed,completed,cancelled',
            ],
            'payment_status' => [
                'required',
                'in:unpaid,partial,paid',
            ],
            'delivery_status' => [
                'required',
                'in:pending,processing,delivered,cancelled',
            ],
            'delivery_address' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'ordered_at' => ['nullable', 'date'],
        ]);

        $validated['total_amount'] =
            (float) $validated['quantity'] *
            (float) $validated['unit_price'];

        $order->update($validated);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Order updated successfully.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()
            ->route('admin.orders.index')
            ->with('status', 'Order deleted successfully.');
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'WGP-'
                . now()->format('YmdHis')
                . '-'
                . random_int(100, 999);
        } while (
            Order::where('order_number', $number)->exists()
        );

        return $number;
    }
}