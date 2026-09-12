@extends('layouts.admin')

@section('title', 'New Order | Wonder Godoro Point')

@push('styles')
<style>
    .order-create{max-width:1050px;margin:0 auto;padding:32px}
    .order-header{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:28px}
    .order-eyebrow{margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#a07a28}
    .order-title{margin:0;font-size:32px;font-weight:800;color:#171717}
    .order-subtitle{margin:7px 0 0;color:#737373;font-size:14px}
    .order-btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 18px;border-radius:9px;text-decoration:none;font-size:14px;font-weight:700;border:1px solid #ddd}
    .order-btn-primary{background:#171717;color:#fff;border-color:#171717}
    .order-btn-secondary{background:#fff;color:#444}
    .order-alert{margin-bottom:20px;padding:14px 17px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:13px}
    .order-alert ul{margin:8px 0 0;padding-left:20px}
    .order-card{background:#fff;border:1px solid #e8e5df;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,.04);overflow:hidden}
    .order-card-head{padding:23px 25px;border-bottom:1px solid #eeeae4}
    .order-card-title{margin:0;font-size:19px;font-weight:800;color:#202020}
    .order-card-text{margin:5px 0 0;font-size:13px;color:#888}
    .order-form{padding:25px}
    .order-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px}
    .order-group{display:flex;flex-direction:column}
    .order-group-full{grid-column:1/-1}
    .order-group label{margin-bottom:7px;font-size:12px;font-weight:750;color:#444}
    .order-group label span{color:#b45309}
    .order-group input,.order-group select,.order-group textarea{
        width:100%;
        box-sizing:border-box;
        border:1px solid #dcdcdc;
        border-radius:8px;
        background:#fff;
        padding:11px 12px;
        font-size:13px;
        color:#333;
        outline:none;
        font-family:inherit;
    }
    .order-group input,.order-group select{height:43px}
    .order-group textarea{resize:vertical;min-height:100px}
    .order-group input:focus,.order-group select:focus,.order-group textarea:focus{
        border-color:#a07a28;
        box-shadow:0 0 0 3px rgba(160,122,40,.10)
    }
    .order-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:25px;padding-top:20px;border-top:1px solid #eeeae4}
    @media(max-width:700px){
        .order-create{padding:20px 14px}
        .order-header{align-items:flex-start;flex-direction:column}
        .order-grid{grid-template-columns:1fr}
        .order-group-full{grid-column:auto}
        .order-form{padding:18px}
        .order-card-head{padding:19px}
    }
</style>
@endpush

@section('content')
<div class="order-create">

    <div class="order-header">
        <div>
            <p class="order-eyebrow">Orders</p>
            <h1 class="order-title">New Order</h1>
            <p class="order-subtitle">Create a new customer order.</p>
        </div>

        <a href="{{ route('admin.orders.index') }}" class="order-btn order-btn-secondary">
            ← Back to Orders
        </a>
    </div>

    @if($errors->any())
        <div class="order-alert">
            <strong>Please fix the following:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="order-card">

        <div class="order-card-head">
            <h2 class="order-card-title">Order Information</h2>
            <p class="order-card-text">Enter the customer and product details below.</p>
        </div>

        <form method="POST" action="{{ route('admin.orders.store') }}" class="order-form">
            @csrf

            <div class="order-grid">

                <div class="order-group order-group-full">
                    <label for="customer_id">Customer <span>*</span></label>
                    <select id="customer_id" name="customer_id" required>
                        <option value="">Select customer</option>
                        @foreach($customers as $customer)
                            <option
                                value="{{ $customer->id }}"
                                @selected(old('customer_id') == $customer->id)
                            >
                                {{ $customer->name }}
                                @if($customer->phone)
                                    — {{ $customer->phone }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="order-group">
                    <label for="product_name">Product <span>*</span></label>
                    <input
                        type="text"
                        id="product_name"
                        name="product_name"
                        value="{{ old('product_name') }}"
                        placeholder="e.g. GOLDSUN Mattress"
                        required
                    >
                </div>

                <div class="order-group">
                    <label for="product_size">Size</label>
                    <input
                        type="text"
                        id="product_size"
                        name="product_size"
                        value="{{ old('product_size') }}"
                        placeholder="e.g. 5x6 Inch 10"
                    >
                </div>

                <div class="order-group">
                    <label for="quantity">Quantity <span>*</span></label>
                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        value="{{ old('quantity', 1) }}"
                        min="1"
                        required
                    >
                </div>

                <div class="order-group">
                    <label for="unit_price">Unit Price (TSh) <span>*</span></label>
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

                <div class="order-group">
                    <label for="status">Order Status <span>*</span></label>
                    <select id="status" name="status" required>
                        <option value="pending" @selected(old('status','pending') === 'pending')>Pending</option>
                        <option value="confirmed" @selected(old('status') === 'confirmed')>Confirmed</option>
                        <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                        <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>

                <div class="order-group">
                    <label for="payment_status">Payment Status <span>*</span></label>
                    <select id="payment_status" name="payment_status" required>
                        <option value="unpaid" @selected(old('payment_status','unpaid') === 'unpaid')>Unpaid</option>
                        <option value="partial" @selected(old('payment_status') === 'partial')>Partial</option>
                        <option value="paid" @selected(old('payment_status') === 'paid')>Paid</option>
                    </select>
                </div>

                <div class="order-group">
                    <label for="delivery_status">Delivery Status <span>*</span></label>
                    <select id="delivery_status" name="delivery_status" required>
                        <option value="pending" @selected(old('delivery_status','pending') === 'pending')>Pending</option>
                        <option value="processing" @selected(old('delivery_status') === 'processing')>Processing</option>
                        <option value="delivered" @selected(old('delivery_status') === 'delivered')>Delivered</option>
                        <option value="cancelled" @selected(old('delivery_status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>

                <div class="order-group">
                    <label for="ordered_at">Order Date & Time</label>
                    <input
                        type="datetime-local"
                        id="ordered_at"
                        name="ordered_at"
                        value="{{ old('ordered_at') }}"
                    >
                </div>

                <div class="order-group order-group-full">
                    <label for="delivery_address">Delivery Address</label>
                    <textarea
                        id="delivery_address"
                        name="delivery_address"
                        rows="4"
                        placeholder="Enter customer's delivery location..."
                    >{{ old('delivery_address') }}</textarea>
                </div>

                <div class="order-group order-group-full">
                    <label for="notes">Order Notes</label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        placeholder="Add any important notes about this order..."
                    >{{ old('notes') }}</textarea>
                </div>

            </div>

            <div class="order-actions">
                <a href="{{ route('admin.orders.index') }}" class="order-btn order-btn-secondary">
                    Cancel
                </a>

                <button type="submit" class="order-btn order-btn-primary">
                    Create Order
                </button>
            </div>

        </form>
    </div>

</div>
@endsection