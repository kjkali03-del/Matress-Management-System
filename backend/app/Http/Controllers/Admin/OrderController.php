<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderActivity;
use App\Models\User;
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
            'products' => Product::active()->with('category')->orderBy('name')->orderBy('size')->get(),
            'drivers' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
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
            'delivery_assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'delivery_area' => ['nullable', 'string', 'max:255'],
            'delivery_notes' => ['nullable', 'string', 'max:5000'],
            'delivery_address' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'ordered_at' => ['nullable', 'date'],
        ]);

        if (! empty($validated['product_id'])) {
            $product = Product::find($validated['product_id']);
            if ($product) {
                $validated['product_name'] = $product->name;
                $validated['product_size'] = $product->size;
                $validated['unit_price'] = $validated['unit_price'] ?? $product->price;
            }
        }

        $validated['total_amount'] =
            (float) $validated['quantity'] *
            (float) $validated['unit_price'];

        $validated['order_number'] = $this->generateOrderNumber();

        $order = Order::create($validated);
        $this->logActivity($order, 'created', 'Order created.');

        return redirect()
            ->route('admin.orders.index')
            ->with('status', 'Order created successfully.');
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'product', 'deliveryAssignee', 'activities.user']);

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
            'products' => Product::active()->with('category')->orderBy('name')->orderBy('size')->get(),
            'drivers' => User::orderBy('name')->get(),
        ]);
    }

    public function update(
        Request $request,
        Order $order
    ): RedirectResponse {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'product_size' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
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
            'delivery_assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'delivery_area' => ['nullable', 'string', 'max:255'],
            'delivery_notes' => ['nullable', 'string', 'max:5000'],
            'delivery_address' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'ordered_at' => ['nullable', 'date'],
        ]);

        if (! empty($validated['product_id'])) {
            $product = Product::find($validated['product_id']);
            if ($product) {
                $validated['product_name'] = $product->name;
                $validated['product_size'] = $product->size;
                $validated['unit_price'] = $validated['unit_price'] ?? $product->price;
            }
        }

        $validated['product_name'] = $validated['product_name'] ?? $order->product_name;
        $validated['unit_price'] = $validated['unit_price'] ?? $order->unit_price;

        $validated['total_amount'] =
            (float) $validated['quantity'] *
            (float) $validated['unit_price'];

        $before = $order->only(['customer_id','product_id','product_name','product_size','quantity','unit_price','total_amount','status','payment_status','delivery_status','delivery_assigned_to','delivery_address','delivery_area','delivered_at','delivery_notes','notes']);
        $order->update($validated);
        $changes = array_keys($order->getChanges());
        $this->logActivity($order, 'updated', 'Order details updated.', ['fields' => $changes, 'before' => $before]);

        if ($order->wasChanged('status')) {
            $this->logActivity($order, 'status', 'Order status changed to ' . ucfirst((string) $order->status) . '.');
        }
        if ($order->wasChanged('payment_status')) {
            $this->logActivity($order, 'payment', 'Payment status changed to ' . ucfirst((string) $order->payment_status) . '.');
        }
        if ($order->wasChanged('delivery_status')) {
            $this->logActivity($order, 'delivery', 'Delivery status changed to ' . ucfirst((string) $order->delivery_status) . '.');
        }
        if ($order->delivery_status === 'delivered' && $order->wasChanged('delivery_status') && !$order->delivered_at) {
            $order->update(['delivered_at' => now()]);
        }

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

    private function logActivity(Order $order, string $type, string $description, ?array $metadata = null): void
    {
        OrderActivity::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata,
        ]);
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