<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');

        $products = Product::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%' . mb_strtolower($search, 'UTF-8') . '%';
                $query->where(function ($q) use ($term): void {
                    $q->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw("LOWER(COALESCE(sku, '')) LIKE ?", [$term])
                        ->orWhereRaw("LOWER(COALESCE(size, '')) LIKE ?", [$term])
                        ->orWhereHas('category', function ($categoryQuery) use ($term): void {
                            $categoryQuery->whereRaw('LOWER(name) LIKE ?', [$term]);
                        });
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'search', 'status'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Keep the product form usable even when an older deployment has not run the category seeder.
        if ($categories->isEmpty()) {
            app(\Database\Seeders\ProductCategorySeeder::class)->run();
            $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        }

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);

        $product = DB::transaction(function () use ($request, $data): Product {
            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('products', 'public');
            }

            $data['is_active'] = $request->boolean('is_active', true);

            return Product::query()->create($data);
        });

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Product created successfully and saved to the product catalogue.');
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