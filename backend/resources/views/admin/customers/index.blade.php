@extends('layouts.admin')
@section('title','Customers | Wonder Godoro Point')
@section('content')
<div class="wgp-customers-page">
<header class="dashboard-topbar"><div><p class="auth-kicker">WONDER GODORO POINT</p><h1>Customers</h1></div><span class="dashboard-date">Manage your customer information.</span></header>
<section class="dashboard-welcome"><div><p class="dashboard-section-kicker">Customer CRM</p><h2>Manage your customer information.</h2><p>Keep contact details, order history and relationship status together in one clean workspace.</p></div><a class="dashboard-primary-action" href="{{ route('admin.inbox') }}">Open Inbox →</a></section>
<section class="dashboard-module-section">
<div class="wgp-customer-toolbar"><form method="GET" action="{{ route('admin.customers.index') }}" class="wgp-customer-search"><span>⌕</span><input type="search" name="search" value="{{ $search }}" placeholder="Search customer name or phone..."><button type="submit" class="wgp-gold-btn">Search</button></form><a href="{{ route('admin.customers.index') }}" class="wgp-gold-btn" style="text-decoration:none">+ Add Customer</a></div>
@if(session('success'))<div class="wgp-toast" role="status">{{ session('success') }}</div>@endif
<div class="wgp-table-wrap"><table class="wgp-table"><thead><tr><th>Customer</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Last Order</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($customers as $customer)
@php $status=$customer->status ?: 'new'; $ordersCount=$customer->orders_count ?? $customer->orders()->count(); $spent=$customer->orders()->sum('total_amount'); $lastOrder=$customer->orders()->latest('ordered_at')->first(); @endphp
<tr><td><a href="{{ route('admin.customers.show',$customer) }}" style="text-decoration:none"><div class="wgp-customer-name"><span class="wgp-avatar">{{ str($customer->name ?: 'C')->substr(0,1)->upper() }}</span><span><strong>{{ $customer->name ?: 'Unnamed Customer' }}</strong><small>{{ $customer->location ?: 'Dar es Salaam' }}</small></span></div></a></td><td>{{ $customer->phone ?: '—' }}</td><td>{{ $ordersCount }}</td><td><strong>TSh {{ number_format($spent,0) }}</strong></td><td>{{ optional($lastOrder?->ordered_at)->format('M j, Y') ?: '—' }}</td><td><span class="wgp-status wgp-status-{{ $status }}">{{ ucfirst($status) }}</span></td><td><a href="{{ route('admin.customers.show',$customer) }}" style="text-decoration:none;font-weight:800;color:#2f76e8">View →</a></td></tr>
@empty<tr><td colspan="7" style="text-align:center;padding:3rem;color:#8995a5">No customers found.</td></tr>@endforelse
</tbody></table></div><div style="margin-top:1rem">{{ $customers->links() }}</div>
</section></div>
@endsection
