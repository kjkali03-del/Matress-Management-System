@extends('layouts.admin')

@section('title', 'Edit ' . $order->order_number . ' | Wonder Godoro Point')

@section('content')
<div class="dashboard-content">

    <div class="dashboard-page-header">
        <div>
            <p class="dashboard-eyebrow">Orders</p>
            <h1>Edit Order</h1>
            <p class="dashboard-subtitle">
                Update {{ $order->order_number }}.
            </p>
        </div>

        <div class="dashboard-page-actions">
            <a
                href="{{ route('admin.orders.show', $order) }}"
                class="dashboard-secondary-button"
            >
                ← Back to Order
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="dashboard-alert dashboard-alert--error">
            <strong>Please fix the following:</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="dashboard-card">

        <div class="dashboard-card-header">
            <div>
                <h2>Order Information</h2>
                <p>
                    Update the customer, product, payment and delivery details.
                </p>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('admin.orders.update', $order) }}"
            class="dashboard-form"
        >
            @csrf
            @method('PUT')

            <div class="dashboard-form-grid">

                <div class="dashboard-form-group dashboard-form-group--full">
                    <label for="customer_id">
                        Customer <span>*</span>
                    </label>

                    <select
                        id="customer_id"
                        name="customer_id"
                        required
                    >
                        <option value="">Select customer</option>

                        @foreach($customers as $customer)
                            <option
                                value="{{ $customer->id }}"
                                {{ old('customer_id', $order->customer_id) == $customer->id ? 'selected' : '' }}
                            >
                                {{ $customer->name }}
                                @if($customer->phone)
                                    — {{ $customer->phone }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="dashboard-form-group">
                    <label for="product_name">
                        Product <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="product_name"
                        name="product_name"
                        value="{{ old('product_name', $order->product_name) }}"
                        required
                    >
                </div>

                <div class="dashboard-form-group">
                    <label for="product_size">
                        Size
                    </label>

                    <input
                        type="text"
                        id="product_size"
                        name="product_size"
                        value="{{ old('product_size', $order->product_size) }}"
                        placeholder="e.g. 5x6 Inch 10"
                    >
                </div>

                <div class="dashboard-form-group">
                    <label for="quantity">
                        Quantity <span>*</span>
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        value="{{ old('quantity', $order->quantity) }}"
                        min="1"
                        required
                    >
                </div>

                <div class="dashboard-form-group">
                    <label for="unit_price">
                        Unit Price (TSh) <span>*</span>
                    </label>

                    <input
                        type="number"
                        id="unit_price"
                        name="unit_price"
                        value="{{ old('unit_price', $order->unit_price) }}"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="dashboard-form-group">
                    <label for="status">
                        Order Status <span>*</span>
                    </label>

                    <select
                        id="status"
                        name="status"
                        required
                    >
                        <option value="pending" {{ old('status', $order->status) === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>

                        <option value="confirmed" {{ old('status', $order->status) === 'confirmed' ? 'selected' : '' }}>
                            Confirmed
                        </option>

                        <option value="completed" {{ old('status', $order->status) === 'completed' ? 'selected' : '' }}>
                            Completed
                        </option>

                        <option value="cancelled" {{ old('status', $order->status) === 'cancelled' ? 'selected' : '' }}>
                            Cancelled
                        </option>
                    </select>
                </div>

                <div class="dashboard-form-group">
                    <label for="payment_status">
                        Payment Status <span>*</span>
                    </label>

                    <select
                        id="payment_status"
                        name="payment_status"
                        required
                    >
                        <option value="unpaid" {{ old('payment_status', $order->payment_status) === 'unpaid' ? 'selected' : '' }}>
                            Unpaid
                        </option>

                        <option value="partial" {{ old('payment_status', $order->payment_status) === 'partial' ? 'selected' : '' }}>
                            Partial
                        </option>

                        <option value="paid" {{ old('payment_status', $order->payment_status) === 'paid' ? 'selected' : '' }}>
                            Paid
                        </option>
                    </select>
                </div>

                <div class="dashboard-form-group">
                    <label for="delivery_status">
                        Delivery Status <span>*</span>
                    </label>

                    <select
                        id="delivery_status"
                        name="delivery_status"
                        required
                    >
                        <option value="pending" {{ old('delivery_status', $order->delivery_status) === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>

                        <option value="processing" {{ old('delivery_status', $order->delivery_status) === 'processing' ? 'selected' : '' }}>
                            Processing
                        </option>

                        <option value="delivered" {{ old('delivery_status', $order->delivery_status) === 'delivered' ? 'selected' : '' }}>
                            Delivered
                        </option>

                        <option value="cancelled" {{ old('delivery_status', $order->delivery_status) === 'cancelled' ? 'selected' : '' }}>
                            Cancelled
                        </option>
                    </select>
                </div>

                <div class="dashboard-form-group dashboard-form-group--full">
                    <label for="delivery_address">
                        Delivery Address
                    </label>

                    <textarea
                        id="delivery_address"
                        name="delivery_address"
                        rows="4"
                        placeholder="Enter customer's delivery location..."
                    >{{ old('delivery_address', $order->delivery_address) }}</textarea>
                </div>

                <div class="dashboard-form-group dashboard-form-group--full">
                    <label for="notes">
                        Order Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        placeholder="Add any important notes about this order..."
                    >{{ old('notes', $order->notes) }}</textarea>
                </div>

                <div class="dashboard-form-group">
                    <label for="ordered_at">
                        Order Date & Time
                    </label>

                    <input
                        type="datetime-local"
                        id="ordered_at"
                        name="ordered_at"
                        value="{{ old('ordered_at', $order->ordered_at?->format('Y-m-d\TH:i')) }}"
                    >
                </div>

            </div>

            <div class="dashboard-form-actions">

                <a
                    href="{{ route('admin.orders.show', $order) }}"
                    class="dashboard-secondary-button"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="dashboard-primary-button"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>
@endsection