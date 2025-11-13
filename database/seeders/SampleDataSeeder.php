<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample categories
        $categories = [
            ['name' => 'Fresh Fish', 'slug' => 'fresh-fish', 'description' => 'Freshly caught fish varieties', 'status' => 'active'],
            ['name' => 'Shellfish', 'slug' => 'shellfish', 'description' => 'Crabs, lobsters, shrimp, and other shellfish', 'status' => 'active'],
            ['name' => 'Processed Seafood', 'slug' => 'processed-seafood', 'description' => 'Smoked, canned, and processed seafood products', 'status' => 'active'],
            ['name' => 'Frozen Seafood', 'slug' => 'frozen-seafood', 'description' => 'Frozen fish and seafood products', 'status' => 'active'],
        ];

        foreach ($categories as $categoryData) {
            Category::firstOrCreate(['name' => $categoryData['name']], $categoryData);
        }

        // Create sample products
        $products = [
            [
                'product_id' => 'SF001',
                'name' => 'Atlantic Salmon',
                'slug' => 'atlantic-salmon',
                'description' => 'Fresh Atlantic salmon fillets',
                'category_id' => Category::where('name', 'Fresh Fish')->first()->id,
                'price' => 25.99,
                'stock_quantity' => 50,
                'min_stock_level' => 10,
                'sku' => 'SALMON-ATL-001',
                'status' => 'active'
            ],
            [
                'product_id' => 'SF002',
                'name' => 'King Crab Legs',
                'slug' => 'king-crab-legs',
                'description' => 'Alaskan king crab legs - premium quality',
                'category_id' => Category::where('name', 'Shellfish')->first()->id,
                'price' => 89.99,
                'stock_quantity' => 15,
                'min_stock_level' => 5,
                'sku' => 'CRAB-KING-001',
                'status' => 'active'
            ],
            [
                'product_id' => 'SF003',
                'name' => 'Smoked Mackerel',
                'slug' => 'smoked-mackerel',
                'description' => 'Traditionally smoked mackerel fillets',
                'category_id' => Category::where('name', 'Processed Seafood')->first()->id,
                'price' => 12.50,
                'stock_quantity' => 8,
                'min_stock_level' => 15, // This will create a low stock alert
                'sku' => 'MACK-SMK-001',
                'status' => 'active'
            ],
            [
                'product_id' => 'SF004',
                'name' => 'Frozen Shrimp',
                'slug' => 'frozen-shrimp',
                'description' => '16/20 count frozen shrimp - 2lb bags',
                'category_id' => Category::where('name', 'Frozen Seafood')->first()->id,
                'price' => 18.75,
                'stock_quantity' => 3,
                'min_stock_level' => 20, // This will also create a low stock alert
                'sku' => 'SHRIMP-FRZ-001',
                'status' => 'active'
            ],
            [
                'product_id' => 'SF005',
                'name' => 'Fresh Tuna Steaks',
                'slug' => 'fresh-tuna-steaks',
                'description' => 'Yellowfin tuna steaks - sushi grade',
                'category_id' => Category::where('name', 'Fresh Fish')->first()->id,
                'price' => 45.00,
                'stock_quantity' => 25,
                'min_stock_level' => 8,
                'sku' => 'TUNA-YEL-001',
                'status' => 'active'
            ],
            [
                'product_id' => 'SF006',
                'name' => 'Live Lobster',
                'slug' => 'live-lobster',
                'description' => 'Live Maine lobsters - 1.5-2lb each',
                'category_id' => Category::where('name', 'Shellfish')->first()->id,
                'price' => 32.00,
                'stock_quantity' => 12,
                'min_stock_level' => 5,
                'sku' => 'LOBSTER-MN-001',
                'status' => 'active'
            ],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['name' => $productData['name']], 
                array_merge($productData, ['created_by' => 1]) // Assuming admin user ID is 1
            );
        }

        // Create some sample stock movements
        $adminUser = \App\Models\User::where('email', 'admin@seafood.com')->first();
        $products = Product::all();

        $stockMovements = [
            [
                'product_id' => $products->where('name', 'Atlantic Salmon')->first()->id,
                'type' => 'in',
                'quantity' => 50,
                'notes' => 'Initial stock',
                'created_by' => $adminUser->id,
                'created_at' => now()->subDays(5),
            ],
            [
                'product_id' => $products->where('name', 'King Crab Legs')->first()->id,
                'type' => 'in',
                'quantity' => 20,
                'notes' => 'Weekly delivery',
                'created_by' => $adminUser->id,
                'created_at' => now()->subDays(3),
            ],
            [
                'product_id' => $products->where('name', 'King Crab Legs')->first()->id,
                'type' => 'out',
                'quantity' => 5,
                'notes' => 'Sold to restaurant',
                'created_by' => $adminUser->id,
                'created_at' => now()->subDays(1),
            ],
            [
                'product_id' => $products->where('name', 'Frozen Shrimp')->first()->id,
                'type' => 'out',
                'quantity' => 17,
                'notes' => 'Large order fulfillment',
                'created_by' => $adminUser->id,
                'created_at' => now()->subHours(6),
            ],
        ];

        foreach ($stockMovements as $movementData) {
            StockMovement::create($movementData);
        }

        $this->command->info('Sample seafood inventory data created successfully!');
        $this->command->info('- 4 categories created');
        $this->command->info('- 6 products created (2 with low stock alerts)');
        $this->command->info('- 4 stock movements created');
    }
}
