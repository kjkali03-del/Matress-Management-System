@extends('layouts.admin')
@section('title', 'Edit Product | Wonder Godoro Point')
@push('styles')
<style>
.product-page{max-width:1050px;margin:0 auto;padding:32px}.product-head{display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:24px}.product-head h1{margin:0;font-size:32px}.product-head p{margin:6px 0 0;color:#737373}.product-card{background:#fff;border:1px solid #e7e2d9;border-radius:16px;padding:26px;box-shadow:0 8px 25px rgba(0,0,0,.04)}.product-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.product-field{display:flex;flex-direction:column;gap:7px}.product-field--full{grid-column:1/-1}.product-field label{font-size:13px;font-weight:700;color:#333}.product-field input,.product-field select,.product-field textarea{width:100%;box-sizing:border-box;border:1px solid #ddd7ce;border-radius:9px;padding:11px 12px;font:inherit;background:#fff}.product-field textarea{resize:vertical}.product-check{display:flex!important;align-items:center;gap:9px}.product-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid #eee}.product-btn{display:inline-flex;padding:11px 17px;border-radius:9px;text-decoration:none;font-weight:700;border:1px solid #ddd;background:#fff;color:#333}.product-btn-primary{background:#171717;color:#fff;border-color:#171717}@media(max-width:700px){.product-page{padding:20px 14px}.product-head{align-items:flex-start;flex-direction:column}.product-form-grid{grid-template-columns:1fr}.product-field--full{grid-column:auto}}
</style>
@endpush
@section('content')
<div class="product-page">
    <div class="product-head"><div><h1>Edit Product</h1><p>Update pricing, cost, stock and availability.</p></div><a class="product-btn" href="{{ route('admin.products.show', $product) }}">← Product</a></div>
    @if($errors->any())<div class="dashboard-alert dashboard-alert--error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="product-card"><form enctype="multipart/form-data" method="POST" action="{{ route('admin.products.update', $product) }}">@csrf @method('PUT') @include('admin.products._form')<div class="product-actions"><a class="product-btn" href="{{ route('admin.products.show', $product) }}">Cancel</a><button class="product-btn product-btn-primary" type="submit">Save Changes</button></div></form></div>
</div>
@endsection
