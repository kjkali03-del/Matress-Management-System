@extends('layouts.admin')

@section('title', 'New Order | Wonder Godoro Point')

@section('content')
<div class="dashboard-content">

    <div class="dashboard-page-header">
        <div>
            <p class="dashboard-eyebrow">Orders</p>
            <h1>New Order</h1>
            <p class="dashboard-subtitle">
                Create a new customer order.
            </p>
        </div>

        <a
            href="{{ route('admin.orders.index') }}"
            class="dashboard-secondary-button"
        >
            ← Back to Orders
        </a>
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
                    Enter the customer and product details below.
                </p>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('admin.orders.store') }}"
            class="dashboard-form"
        >
            @csrf

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
                                {{ old('customer_id') == $customer->id ? 'selected' : '' }}
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
                        value="{{ old('product_name') }}"
                        placeholder="e.g. GOLDSUN Mattress"
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
                        value="{{ old('product_size') }}"
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
                        value="{{ old('quantity', 1) }}"
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
                        value="{{ old('unit_price') }}"
                        min="0"
                        step="0.01"
                        placeholder="e.g. 190000"
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
                        <option value="pending" {{ old('status', 'pending') === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>

                        <option value="confirmed" {{ old('status') === 'confirmed' ? 'selected' : '' }}>
                            Confirmed
                        </option>

                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>
                            Completed
                        </option>

                        <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>
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
                        <option value="unpaid" {{ old('payment_status', 'unpaid') === 'unpaid' ? 'selected' : '' }}>
                            Unpaid
                        </option>

                        <option value="partial" {{ old('payment_status') === 'partial' ? 'selected' : '' }}>
                            Partial
                        </option>

                        <option value="paid" {{ old('payment_status') === 'paid' ? 'selected' : '' }}>
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
                        <option value="pending" {{ old('delivery_status', 'pending') === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>

                        <option value="processing" {{ old('delivery_status') === 'processing' ? 'selected' : '' }}>
                            Processing
                        </option>

                        <option value="delivered" {{ old('delivery_status') === 'delivered' ? 'selected' : '' }}>
                            Delivered
                        </option>

                        <option value="cancelled" {{ old('delivery_status') === 'cancelled' ? 'selected' : '' }}>
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
                    >{{ old('delivery_address') }}</textarea>
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
                    >{{ old('notes') }}</textarea>
                </div>

                <div class="dashboard-form-group">
                    <label for="ordered_at">
                        Order Date & Time
                    </label>

                    <input
                        type="datetime-local"
                        id="ordered_at"
                        name="ordered_at"
                        value="{{ old('ordered_at') }}"
                    >
                </div>

            </div>

            <div class="dashboard-form-actions">

                <a
                    href="{{ route('admin.orders.index') }}"
                    class="dashboard-secondary-button"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="dashboard-primary-button"
                >
                    Create Order
                </button>

            </div>

        </form>

    </div>

</div>
@endsection