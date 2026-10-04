@extends('layouts.admin')

@section('title', 'Products | Wonder Godoro Point')

@section('content')
<div class="products-page">

    <div class="products-header">
        <div>
            <h1>Products</h1>
            <p>Manage your mattress products, prices and availability.</p>
        </div>

        <a href="{{ route('admin.products.create') }}" class="btn-primary">
            + Add Product
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success wgp-product-success" data-wgp-reveal>
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.products.index') }}" class="wgp-product-toolbar" data-wgp-reveal>
        <div class="wgp-product-search">
            <span aria-hidden="true">⌕</span>
            <input type="search" name="search" value="{{ $search ?? '' }}" placeholder="Search product, SKU, size or category..." aria-label="Search products">
        </div>
        <select name="status" aria-label="Filter products by status">
            <option value="all" @selected(($status ?? 'all') === 'all')>All products</option>
            <option value="active" @selected(($status ?? '') === 'active')>Active</option>
            <option value="inactive" @selected(($status ?? '') === 'inactive')>Inactive</option>
        </select>
        <button type="submit" class="btn-primary">Search</button>
        @if(($search ?? '') !== '' || ($status ?? 'all') !== 'all')
            <a href="{{ route('admin.products.index') }}" class="wgp-product-reset">Reset</a>
        @endif
    </form>

    @if($products->count())
        <div class="products-card" data-wgp-reveal>
            <div class="table-wrapper">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Size</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($products as $product)
                            <tr data-wgp-reveal>
                                <td>
                                    <div class="wgp-product-listing">
                                        @if($product->image_path)<img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}">@else<div class="wgp-product-listing-placeholder">▣</div>@endif
                                        <div><div class="product-name">{{ $product->name }}</div>

                                    @if($product->description)
                                        <div class="product-description">
                                            {{ Str::limit($product->description, 60) }}
                                        </div>
                                    @endif</div></div>
                                </td>

                                <td>
                                    {{ $product->category->name ?? 'Uncategorized' }}
                                </td>

                                <td>
                                    {{ $product->size ?: '—' }}
                                </td>

                                <td>
                                    TSh {{ number_format((float) $product->price, 0) }}
                                    @if($product->cost_price > 0)<div class="product-description">Cost: TSh {{ number_format((float) $product->cost_price, 0) }}</div>@endif
                                </td>

                                <td>
                                    {{ $product->stock_quantity }}
                                    @if($product->stock_quantity <= $product->reorder_level)
                                        <div class="product-description">Reorder</div>
                                    @endif
                                </td>

                                <td>
                                    @if($product->is_active)
                                        <span class="status-active">Active</span>
                                    @else
                                        <span class="status-inactive">Inactive</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="product-actions">
                                        <a
                                            href="{{ route('admin.products.show', $product) }}"
                                            class="action-view"
                                        >
                                            View
                                        </a>

                                        <a
                                            href="{{ route('admin.products.edit', $product) }}"
                                            class="action-edit"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.products.destroy', $product) }}"
                                            onsubmit="return confirm('Are you sure you want to delete this product?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="action-delete">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="products-pagination">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="products-empty">
            <div class="empty-icon">🛏️</div>

            <h2>No products yet</h2>

            <p>
                Start by adding your first mattress product.
            </p>

            <a href="{{ route('admin.products.create') }}" class="btn-primary">
                + Add First Product
            </a>
        </div>
    @endif

</div>
@endsection