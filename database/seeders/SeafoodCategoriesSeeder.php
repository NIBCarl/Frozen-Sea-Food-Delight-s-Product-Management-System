<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SeafoodCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Fresh Fish',
                'slug' => 'fresh-fish',
                'description' => 'Fresh caught fish from Cebu waters, delivered daily',
                'status' => 'active',
            ],
            [
                'name' => 'Frozen Fish',
                'slug' => 'frozen-fish',
                'description' => 'Premium frozen fish, flash-frozen to maintain freshness',
                'status' => 'active',
            ],
            [
                'name' => 'Shellfish',
                'slug' => 'shellfish',
                'description' => 'Fresh and frozen shellfish including scallops, oysters, and mussels',
                'status' => 'active',
            ],
            [
                'name' => 'Crustaceans',
                'slug' => 'crustaceans',
                'description' => 'Crabs, lobsters, shrimp, and prawns from Cebu waters',
                'status' => 'active',
            ],
            [
                'name' => 'Mollusks',
                'slug' => 'mollusks',
                'description' => 'Squid, octopus, and other cephalopods',
                'status' => 'active',
            ],
            [
                'name' => 'Processed Seafood',
                'slug' => 'processed-seafood',
                'description' => 'Dried, smoked, and processed seafood products',
                'status' => 'active',
            ],
            [
                'name' => 'Premium Catch',
                'slug' => 'premium-catch',
                'description' => 'High-grade seafood including tuna, grouper, and specialty fish',
                'status' => 'active',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('Seafood categories seeded successfully!');
    }
}
