@extends('layouts.admin')

@section('title', 'Orders | Wonder Godoro Point')

@push('styles')
<style>
    .orders-page{max-width:1500px;margin:0 auto;padding:32px}
    .orders-header{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:28px}
    .orders-eyebrow{margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#a07a28}
    .orders-title{margin:0;font-size:32px;font-weight:800;color:#171717}
    .orders-subtitle{margin:7px 0 0;color:#737373;font-size:14px}
    .orders-btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 18px;border-radius:9px;text-decoration:none;font-size:14px;font-weight:700;border:1px solid #dedede;cursor:pointer}
    .orders-btn-primary{background:#171717;color:#fff;border-color:#171717}
    .orders-btn-primary:hover{background:#000}
    .orders-btn-secondary{background:#fff;color:#333}
    .orders-alert{margin-bottom:20px;padding:13px 16px;border-radius:10px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:14px}
    .orders-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
    .orders-card{background:#fff;border:1px solid #e8e5df;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,.04)}
    .orders-stat{padding:21px}
    .orders-stat-label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#888}
    .orders-stat-value{margin:9px 0 4px;font-size:25px;font-weight:800;color:#171717}
    .orders-stat-help{font-size:12px;color:#999}
    .orders-section{padding:22px;margin-bottom:20px}
    .orders-section-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
    .orders-section-title{margin:0;font-size:18px;font-weight:800;color:#202020}
    .orders-section-text{margin:4px 0 0;font-size:13px;color:#888}
    .orders-filters{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:12px;align-items:end}
    .orders-field label{display:block;margin-bottom:7px;font-size:12px;font-weight:700;color:#555}
    .orders-field input,.orders-field select{width:100%;box-sizing:border-box;height:42px;border:1px solid #dcdcdc;border-radius:8px;background:#fff;padding:0 12px;font-size:13px;color:#333;outline:none}
    .orders-field input:focus,.orders-field select:focus{border-color:#a07a28;box-shadow:0 0 0 3px rgba(160,122,40,.10)}
    .orders-filter-actions{display:flex;gap:8px}
    .orders-table-wrap{overflow-x:auto}
    .orders-table{width:100%;border-collapse:collapse;min-width:1050px}
    .orders-table th{padding:13px 12px;text-align:left;border-bottom:1px solid #e8e5df;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#888}
    .orders-table td{padding:15px 12px;border-bottom:1px solid #f0efec;vertical-align:top;font-size:13px;color:#333}
    .orders-table tbody tr:hover{background:#faf9f6}
    .orders-main{display:block;font-weight:750;color:#222}
    .orders-muted{display:block;margin-top:4px;font-size:11px;color:#999}
    .orders-badge{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;text-transform:capitalize;background:#f3f4f6;color:#555}
    .orders-badge--pending{background:#fff7ed;color:#c2410c}
    .orders-badge--confirmed{background:#eff6ff;color:#1d4ed8}
    .orders-badge--completed{background:#f0fdf4;color:#15803d}
    .orders-badge--cancelled{background:#fef2f2;color:#b91c1c}
    .orders-badge--paid{background:#f0fdf4;color:#15803d}
    .orders-badge--partial{background:#fffbeb;color:#a16207}
    .orders-badge--unpaid{background:#f5f5f5;color:#555}
    .orders-badge--processing{background:#eff6ff;color:#1d4ed8}
    .orders-badge--delivered{background:#f0fdf4;color:#15803d}
    .orders-actions{display:flex;gap:7px}
    .orders-action{display:inline-flex;padding:7px 10px;border:1px solid #ddd;border-radius:7px;text-decoration:none;color:#444;font-size:11px;font-weight:700;background:#fff}
    .orders-action:hover{background:#f7f6f3}
    .orders-empty{text-align:center;padding:55px 20px;color:#777}
    .orders-empty-icon{font-size:34px;margin-bottom:12px}
    .orders-empty h3{margin:0;color:#222;font-size:18px}
    .orders-empty p{font-size:13px;margin:8px 0 18px}
    .orders-pagination{margin-top:20px}
    @media(max-width:1100px){
        .orders-stats{grid-template-columns:repeat(2,1fr)}
        .orders-filters{grid-template-columns:1fr 1fr}
        .orders-filter-actions{grid-column:1/-1}
    }
    @media(max-width:650px){
        .orders-page{padding:20px 14px}
        .orders-header{align-items:flex-start;flex-direction:column}
        .orders-stats{grid-template-columns:1fr}
        .orders-filters{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
<div class="orders-page">

    <div class="orders-header">
        <div>
            <p class="orders-eyebrow">Sales Management</p>
            <h1 class="orders-title">Orders</h1>
            <p class="orders-subtitle">Manage customer orders, payments and deliveries.</p>
        </div>

        <a href="{{ route('admin.orders.create') }}" class="orders-btn orders-btn-primary">
            + New Order
        </a>
    </div>

    @if(session('status'))
        <div class="orders-alert">{{ session('status') }}</div>
    @endif

    <div class="orders-stats">
        <div class="orders-card orders-stat">
            <div class="orders-stat-label">Total Orders</div>
            <div class="orders-stat-value">{{ number_format($summary['total_orders']) }}</div>
            <div class="orders-stat-help">All customer orders</div>
        </div>

        <div class="orders-card orders-stat">
            <div class="orders-stat-label">Pending</div>
            <div class="orders-stat-value">{{ number_format($summary['pending_orders']) }}</div>
            <div class="orders-stat-help">Awaiting confirmation</div>
        </div>

        <div class="orders-card orders-stat">
            <div class="orders-stat-label">Completed</div>
            <div class="orders-stat-value">{{ number_format($summary['completed_orders']) }}</div>
            <div class="orders-stat-help">Completed orders</div>
        </div>

        <div class="orders-card orders-stat">
            <div class="orders-stat-label">Paid Sales</div>
            <div class="orders-stat-value">TSh {{ number_format((float) $summary['total_sales'], 0) }}</div>
            <div class="orders-stat-help">Orders marked as paid</div>
        </div>
    </div>

    <div class="orders-card orders-section">
        <div class="orders-section-head">
            <div>
                <h2 class="orders-section-title">Find Orders</h2>
                <p class="orders-section-text">Search and filter your orders.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.orders.index') }}" class="orders-filters">

            <div class="orders-field">
                <label for="search">Search</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Order number, customer, phone or product..."
                >
            </div>

            <div class="orders-field">
                <label for="status">Order Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    <option value="pending" @selected($status === 'pending')>Pending</option>
                    <option value="confirmed" @selected($status === 'confirmed')>Confirmed</option>
                    <option value="completed" @selected($status === 'completed')>Completed</option>
                    <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div class="orders-field">
                <label for="payment_status">Payment</label>
                <select id="payment_status" name="payment_status">
                    <option value="">All payments</option>
                    <option value="unpaid" @selected($paymentStatus === 'unpaid')>Unpaid</option>
                    <option value="partial" @selected($paymentStatus === 'partial')>Partial</option>
                    <option value="paid" @selected($paymentStatus === 'paid')>Paid</option>
                </select>
            </div>

            <div class="orders-field">
                <label for="delivery_status">Delivery</label>
                <select id="delivery_status" name="delivery_status">
                    <option value="">All deliveries</option>
                    <option value="pending" @selected($deliveryStatus === 'pending')>Pending</option>
                    <option value="processing" @selected($deliveryStatus === 'processing')>Processing</option>
                    <option value="delivered" @selected($deliveryStatus === 'delivered')>Delivered</option>
                    <option value="cancelled" @selected($deliveryStatus === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div class="orders-filter-actions">
                <button type="submit" class="orders-btn orders-btn-primary">Search</button>
                <a href="{{ route('admin.orders.index') }}" class="orders-btn orders-btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <div class="orders-card orders-section">
        <div class="orders-section-head">
            <div>
                <p class="orders-eyebrow">Order Management</p>
                <h2 class="orders-section-title">All Orders</h2>
                <p class="orders-section-text">
                    Showing {{ $orders->count() }} of {{ $orders->total() }} orders
                </p>
            </div>
        </div>

        @if($orders->count())

            <div class="orders-table-wrap">
                <table class="orders-table">
                    <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Delivery</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td>
                                <span class="orders-main">{{ $order->order_number }}</span>
                                <span class="orders-muted">
                                    {{ $order->ordered_at?->format('d M Y H:i') ?? $order->created_at->format('d M Y H:i') }}
                                </span>
                            </td>

                            <td>
                                <span class="orders-main">{{ $order->customer->name ?? 'Unknown Customer' }}</span>
                                @if($order->customer?->phone)
                                    <span class="orders-muted">{{ $order->customer->phone }}</span>
                                @endif
                            </td>

                            <td>
                                <span class="orders-main">{{ $order->product_name }}</span>
                                @if($order->product_size)
                                    <span class="orders-muted">Size: {{ $order->product_size }}</span>
                                @endif
                                <span class="orders-muted">Qty: {{ $order->quantity }}</span>
                            </td>

                            <td>
                                <span class="orders-main">
                                    TSh {{ number_format((float) $order->total_amount, 0) }}
                                </span>
                            </td>

                            <td>
                                <span class="orders-badge orders-badge--{{ $order->status }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>

                            <td>
                                <span class="orders-badge orders-badge--{{ $order->payment_status }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </td>

                            <td>
                                <span class="orders-badge orders-badge--{{ $order->delivery_status }}">
                                    {{ ucfirst($order->delivery_status) }}
                                </span>
                            </td>

                            <td>
                                <div class="orders-actions">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="orders-action">View</a>
                                    <a href="{{ route('admin.orders.edit', $order) }}" class="orders-action">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="orders-pagination">
                    {{ $orders->links() }}
                </div>
            @endif

        @else

            <div class="orders-empty">
                <div class="orders-empty-icon">📦</div>
                <h3>No matching orders</h3>

                @if($search || $status || $paymentStatus || $deliveryStatus)
                    <p>No orders match your current search or filters.</p>
                    <a href="{{ route('admin.orders.index') }}" class="orders-btn orders-btn-secondary">
                        Clear Filters
                    </a>
                @else
                    <p>Create your first customer order to start tracking sales.</p>
                    <a href="{{ route('admin.orders.create') }}" class="orders-btn orders-btn-primary">
                        Create First Order
                    </a>
                @endif
            </div>

        @endif
    </div>

</div>
@endsection