<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_product_and_retrieve_it_from_the_catalogue(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = ProductCategory::create([
            'name' => 'Test Category',
            'description' => 'Test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'product_category_id' => $category->id,
            'name' => 'TEST GOLDSUN',
            'sku' => 'TEST-GOLD-001',
            'size' => '5x6 Inch 10',
            'price' => 190000,
            'cost_price' => 150000,
            'stock_quantity' => 10,
            'reorder_level' => 2,
            'description' => 'Catalog persistence test',
            'is_active' => 1,
        ]);

        $product = Product::query()->where('sku', 'TEST-GOLD-001')->first();

        $response->assertRedirect(route('admin.products.show', $product));
        $this->assertNotNull($product);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'TEST GOLDSUN',
            'sku' => 'TEST-GOLD-001',
            'stock_quantity' => 10,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['search' => 'TEST GOLDSUN']))
            ->assertOk()
            ->assertSee('TEST GOLDSUN');
    }
}
