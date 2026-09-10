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
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($products->count())
        <div class="products-card">
            <div class="table-wrapper">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Size</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>
                                    <div class="product-name">
                                        {{ $product->name }}
                                    </div>

                                    @if($product->description)
                                        <div class="product-description">
                                            {{ Str::limit($product->description, 60) }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    {{ $product->category->name ?? 'Uncategorized' }}
                                </td>

                                <td>
                                    {{ $product->size ?: '—' }}
                                </td>

                                <td>
                                    TSh {{ number_format((float) $product->price, 0) }}
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