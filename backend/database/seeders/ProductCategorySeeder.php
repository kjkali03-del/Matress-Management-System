<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {$this->call([
    ProductCategorySeeder::class,
]);
        $categories = [
            [
                'name' => 'Orthopedic',
                'description' => 'Orthopedic mattresses designed for supportive sleep.',
                'is_active' => true,
            ],
            [
                'name' => 'High Density',
                'description' => 'High-density foam mattresses for everyday comfort and durability.',
                'is_active' => true,
            ],
            [
                'name' => 'Memory Foam',
                'description' => 'Memory foam mattresses designed for pressure-relieving comfort.',
                'is_active' => true,
            ],
            [
                'name' => 'Spring',
                'description' => 'Spring mattresses with responsive support.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            ProductCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
