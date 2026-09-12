@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number . ' | Wonder Godoro Point')

@section('content')
<div class="dashboard-content">

    <div class="dashboard-page-header">
        <div>
            <p class="dashboard-eyebrow">Order Details</p>
            <h1>{{ $order->order_number }}</h1>
            <p class="dashboard-subtitle">
                View and manage this customer order.
            </p>
        </div>

        <div class="dashboard-page-actions">
            <a
                href="{{ route('admin.orders.edit', $order) }}"
                class="dashboard-primary-button"
            >
                Edit Order
            </a>

            <a
                href="{{ route('admin.orders.index') }}"
                class="dashboard-secondary-button"
            >
                ← Orders
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="dashboard-alert dashboard-alert--success">
            {{ session('status') }}
        </div>
    @endif

    <div class="dashboard-grid dashboard-grid--two">

        <div class="dashboard-card">

            <div class="dashboard-card-header">
                <div>
                    <h2>Order Information</h2>
                    <p>Basic details of this order.</p>
                </div>
            </div>

            <div class="dashboard-detail-list">

                <div class="dashboard-detail-row">
                    <span>Order Number</span>
                    <strong>{{ $order->order_number }}</strong>
                </div>

                <div class="dashboard-detail-row">
                    <span>Customer</span>
                    <strong>
                        {{ $order->customer->name ?? 'Unknown Customer' }}
                    </strong>
                </div>

                <div class="dashboard-detail-row">
                    <span>Phone</span>
                    <strong>
                        {{ $order->customer->phone ?? 'N/A' }}
                    </strong>
                </div>

                <div class="dashboard-detail-row">
                    <span>Product</span>
                    <strong>{{ $order->product_name }}</strong>
                </div>

                <div class="dashboard-detail-row">
                    <span>Size</span>
                    <strong>{{ $order->product_size ?: 'N/A' }}</strong>
                </div>

                <div class="dashboard-detail-row">
                    <span>Quantity</span>
                    <strong>{{ $order->quantity }}</strong>
                </div>

                <div class="dashboard-detail-row">
                    <span>Unit Price</span>
                    <strong>
                        TSh {{ number_format((float) $order->unit_price, 0) }}
                    </strong>
                </div>

                <div class="dashboard-detail-row dashboard-detail-row--total">
                    <span>Total Amount</span>
                    <strong>
                        TSh {{ number_format((float) $order->total_amount, 0) }}
                    </strong>
                </div>

            </div>

        </div>

        <div class="dashboard-card">

            <div class="dashboard-card-header">
                <div>
                    <h2>Order Status</h2>
                    <p>Current progress of this order.</p>
                </div>
            </div>

            <div class="dashboard-status-stack">

                <div class="dashboard-status-item">
                    <span>Order Status</span>

                    <span class="dashboard-status-badge dashboard-status-badge--{{ $order->status }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>

                <div class="dashboard-status-item">
                    <span>Payment</span>

                    <span class="dashboard-status-badge dashboard-status-badge--{{ $order->payment_status }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>

                <div class="dashboard-status-item">
                    <span>Delivery</span>

                    <span class="dashboard-status-badge dashboard-status-badge--{{ $order->delivery_status }}">
                        {{ ucfirst($order->delivery_status) }}
                    </span>
                </div>

                <div class="dashboard-status-item">
                    <span>Ordered At</span>

                    <strong>
                        {{ $order->ordered_at?->format('d M Y H:i') ?? $order->created_at->format('d M Y H:i') }}
                    </strong>
                </div>

            </div>

        </div>

    </div>

    <div class="dashboard-card">

        <div class="dashboard-card-header">
            <div>
                <h2>Delivery Information</h2>
                <p>Customer delivery details.</p>
            </div>
        </div>

        <div class="dashboard-detail-block">
            <span>Delivery Address</span>

            <p>
                {{ $order->delivery_address ?: 'No delivery address added.' }}
            </p>
        </div>

    </div>

    <div class="dashboard-card">

        <div class="dashboard-card-header">
            <div>
                <h2>Order Notes</h2>
                <p>Additional information about this order.</p>
            </div>
        </div>

        <div class="dashboard-detail-block">
            <p>
                {{ $order->notes ?: 'No notes have been added.' }}
            </p>
        </div>

    </div>

    <div class="dashboard-card dashboard-danger-card">

        <div class="dashboard-card-header">
            <div>
                <h2>Delete Order</h2>
                <p>
                    Deleting an order cannot be undone.
                </p>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('admin.orders.destroy', $order) }}"
            onsubmit="return confirm('Are you sure you want to delete this order?');"
        >
            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="dashboard-danger-button"
            >
                Delete Order
            </button>
        </form>

    </div>

</div>
@endsection