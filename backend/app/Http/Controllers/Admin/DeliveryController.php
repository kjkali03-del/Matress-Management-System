<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->input('status', 'pending');
        $allowed = ['pending', 'processing', 'delivered', 'cancelled'];

        $orders = Order::query()
            ->with(['customer', 'deliveryAssignee', 'product'])
            ->when(in_array($status, $allowed, true), fn ($q) => $q->where('delivery_status', $status))
            ->orderByRaw("CASE delivery_status WHEN 'processing' THEN 1 WHEN 'pending' THEN 2 WHEN 'delivered' THEN 3 ELSE 4 END")
            ->orderByDesc('ordered_at')
            ->paginate(20)
            ->withQueryString();

        $drivers = User::query()->orderBy('name')->get();

        $counts = collect($allowed)->mapWithKeys(fn ($value) => [$value => Order::where('delivery_status', $value)->count()]);

        return view('admin.delivery.index', compact('orders', 'drivers', 'status', 'counts'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_status' => ['required', 'in:pending,processing,delivered,cancelled'],
            'delivery_assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'delivery_area' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:5000'],
            'delivery_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($validated['delivery_status'] === 'delivered') {
            $validated['delivered_at'] = $order->delivered_at ?? now();
            $validated['status'] = 'completed';
        } elseif ($order->delivery_status === 'delivered') {
            $validated['delivered_at'] = null;
        }

        $order->update($validated);

        return back()->with('status', "Delivery {$order->order_number} updated successfully.");
    }
}
