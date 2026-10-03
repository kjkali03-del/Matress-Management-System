<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(): View
    {
        $products = Product::with('category')
            ->latest()
            ->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        $data['is_active'] = $request->boolean('is_active', true);
        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        $product->load('category');

        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product.
     */
    public function update(
        UpdateProductRequest $request,
        Product $product
    ): RedirectResponse {
        $data = $request->validated();
        unset($data['image']);
        if ($request->hasFile('image')) {
            if ($product->image_path) { Storage::disk('public')->delete($product->image_path); }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        $data['is_active'] = $request->boolean('is_active');
        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}