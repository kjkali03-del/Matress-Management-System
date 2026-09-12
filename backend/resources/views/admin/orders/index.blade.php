@extends('layouts.admin')

@section('title', 'Orders | Wonder Godoro Point')

@section('content')
<div class="dashboard-content">

    <div class="dashboard-page-header">
        <div>
            <p class="dashboard-eyebrow">Sales Management</p>
            <h1>Orders</h1>
            <p class="dashboard-subtitle">
                Manage customer orders, payments and deliveries.
            </p>
        </div>

        <a
            href="{{ route('admin.orders.create') }}"
            class="dashboard-primary-button"
        >
            + New Order
        </a>
    </div>

    @if(session('status'))
        <div class="dashboard-alert dashboard-alert--success">
            {{ session('status') }}
        </div>
    @endif

    {{-- Summary --}}
    <div class="dashboard-grid dashboard-grid--4">

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div>
                    <p class="dashboard-eyebrow">Orders</p>
                    <h2>{{ number_format($summary['total_orders']) }}</h2>
                    <p>Total Orders</p>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div>
                    <p class="dashboard-eyebrow">Pending</p>
                    <h2>{{ number_format($summary['pending_orders']) }}</h2>
                    <p>Awaiting confirmation</p>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div>
                    <p class="dashboard-eyebrow">Completed</p>
                    <h2>{{ number_format($summary['completed_orders']) }}</h2>
                    <p>Completed orders</p>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div>
                    <p class="dashboard-eyebrow">Paid Sales</p>
                    <h2>
                        TSh {{ number_format((float) $summary['total_sales'], 0) }}
                    </h2>
                    <p>Orders marked as paid</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Filters --}}
    <div class="dashboard-card">

        <div class="dashboard-card-header">
            <div>
                <h2>Find Orders</h2>
                <p>Search and filter your orders.</p>
            </div>
        </div>

        <form
            method="GET"
            action="{{ route('admin.orders.index') }}"
            class="dashboard-filter-form"
        >

            <div class="dashboard-filter-field dashboard-filter-field--search">
                <label for="search">Search</label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Order number, customer, phone or product..."
                >
            </div>

            <div class="dashboard-filter-field">
                <label for="status">Order Status</label>

                <select id="status" name="status">
                    <option value="">All statuses</option>
                    <option value="pending" @selected($status === 'pending')>
                        Pending
                    </option>
                    <option value="confirmed" @selected($status === 'confirmed')>
                        Confirmed
                    </option>
                    <option value="completed" @selected($status === 'completed')>
                        Completed
                    </option>
                    <option value="cancelled" @selected($status === 'cancelled')>
                        Cancelled
                    </option>
                </select>
            </div>

            <div class="dashboard-filter-field">
                <label for="payment_status">Payment</label>

                <select id="payment_status" name="payment_status">
                    <option value="">All payments</option>
                    <option value="unpaid" @selected($paymentStatus === 'unpaid')>
                        Unpaid
                    </option>
                    <option value="partial" @selected($paymentStatus === 'partial')>
                        Partial
                    </option>
                    <option value="paid" @selected($paymentStatus === 'paid')>
                        Paid
                    </option>
                </select>
            </div>

            <div class="dashboard-filter-field">
                <label for="delivery_status">Delivery</label>

                <select id="delivery_status" name="delivery_status">
                    <option value="">All deliveries</option>
                    <option value="pending" @selected($deliveryStatus === 'pending')>
                        Pending
                    </option>
                    <option value="processing" @selected($deliveryStatus === 'processing')>
                        Processing
                    </option>
                    <option value="delivered" @selected($deliveryStatus === 'delivered')>
                        Delivered
                    </option>
                    <option value="cancelled" @selected($deliveryStatus === 'cancelled')>
                        Cancelled
                    </option>
                </select>
            </div>

            <div class="dashboard-filter-actions">
                <button
                    type="submit"
                    class="dashboard-primary-button"
                >
                    Search
                </button>

                <a
                    href="{{ route('admin.orders.index') }}"
                    class="dashboard-secondary-button"
                >
                    Clear
                </a>
            </div>

        </form>

    </div>

    {{-- Orders --}}
    <div class="dashboard-card">

        <div class="dashboard-card-header">
            <div>
                <p class="dashboard-eyebrow">Order Management</p>

                <h2>All Orders</h2>

                <p>
                    Showing {{ $orders->count() }}
                    of {{ $orders->total() }} orders
                </p>
            </div>
        </div>

        @if($orders->count())

            <div class="dashboard-table-wrap">

                <table class="dashboard-table">

                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Delivery</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($orders as $order)

                            <tr>

                                {{-- Order --}}
                                <td>
                                    <strong>
                                        {{ $order->order_number }}
                                    </strong>

                                    <small class="dashboard-table-muted">
                                        {{
                                            $order->ordered_at?->format('d M Y H:i')
                                            ?? $order->created_at->format('d M Y H:i')
                                        }}
                                    </small>
                                </td>

                                {{-- Customer --}}
                                <td>
                                    <strong>
                                        {{ $order->customer->name ?? 'Unknown Customer' }}
                                    </strong>

                                    @if($order->customer?->phone)
                                        <small class="dashboard-table-muted">
                                            {{ $order->customer->phone }}
                                        </small>
                                    @endif
                                </td>

                                {{-- Product --}}
                                <td>
                                    <strong>
                                        {{ $order->product_name }}
                                    </strong>

                                    @if($order->product_size)
                                        <small class="dashboard-table-muted">
                                            Size: {{ $order->product_size }}
                                        </small>
                                    @endif

                                    <small class="dashboard-table-muted">
                                        Qty: {{ $order->quantity }}
                                    </small>

                                    <small class="dashboard-table-muted">
                                        Unit:
                                        TSh {{ number_format((float) $order->unit_price, 0) }}
                                    </small>
                                </td>

                                {{-- Total --}}
                                <td>
                                    <strong>
                                        TSh {{ number_format((float) $order->total_amount, 0) }}
                                    </strong>
                                </td>

                                {{-- Order status --}}
                                <td>
                                    <span
                                        class="dashboard-status-badge dashboard-status-badge--{{ $order->status }}"
                                    >
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>

                                {{-- Payment --}}
                                <td>
                                    <span
                                        class="dashboard-status-badge dashboard-status-badge--{{ $order->payment_status }}"
                                    >
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </td>

                                {{-- Delivery --}}
                                <td>
                                    <span
                                        class="dashboard-status-badge dashboard-status-badge--{{ $order->delivery_status }}"
                                    >
                                        {{ ucfirst($order->delivery_status) }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <div class="dashboard-table-actions">

                                        <a
                                            href="{{ route('admin.orders.show', $order) }}"
                                            class="dashboard-table-action"
                                        >
                                            View
                                        </a>

                                        <a
                                            href="{{ route('admin.orders.edit', $order) }}"
                                            class="dashboard-table-action"
                                        >
                                            Edit
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            @if($orders->hasPages())
                <div class="dashboard-pagination">
                    {{ $orders->links() }}
                </div>
            @endif

        @else

            <div class="dashboard-empty-state">

                <div class="dashboard-empty-icon">
                    #
                </div>

                <h3>No matching orders</h3>

                @if($search || $status || $paymentStatus || $deliveryStatus)

                    <p>
                        No orders match your current search or filters.
                    </p>

                    <a
                        href="{{ route('admin.orders.index') }}"
                        class="dashboard-secondary-button"
                    >
                        Clear Filters
                    </a>

                @else

                    <p>
                        Create your first customer order to start tracking sales.
                    </p>

                    <a
                        href="{{ route('admin.orders.create') }}"
                        class="dashboard-primary-button"
                    >
                        Create First Order
                    </a>

                @endif

            </div>

        @endif

    </div>

</div>
@endsection