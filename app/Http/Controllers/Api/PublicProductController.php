<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    /**
     * Display a listing of public products
     * Only shows active and available products for guest users
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'primaryImage'])
            ->where('is_available', true)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc');

        // Apply filters if provided
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('fish_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('fish_type')) {
            $query->where('fish_type', $request->input('fish_type'));
        }

        if ($request->boolean('frozen_only')) {
            $query->where('is_frozen', true);
        }

        if ($request->filled('freshness_grade')) {
            $query->where('freshness_grade', $request->input('freshness_grade'));
        }

        if ($request->filled('origin_waters')) {
            $query->where('origin_waters', 'like', "%{$request->input('origin_waters')}%");
        }

        if ($request->boolean('featured')) {
            $query->where('featured', true);
        }

        // Get products with pagination
        $perPage = $request->input('per_page', 20);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        $product = Product::with(['category', 'primaryImage', 'images'])
            ->where('is_available', true)
            ->where('status', 'active')
            ->findOrFail($id);

        return response()->json($product);
    }

    /**
     * Get available categories with product counts
     */
    public function categories()
    {
        $categories = \App\Models\Category::withCount([
            'products' => function($query) {
                $query->where('is_available', true)
                      ->where('status', 'active');
            }
        ])->having('products_count', '>', 0)
          ->get();

        return response()->json($categories);
    }

    /**
     * Get featured products
     */
    public function featured()
    {
        $products = Product::with(['category', 'primaryImage'])
            ->where('is_available', true)
            ->where('status', 'active')
            ->where('featured', true)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return response()->json($products);
    }

    /**
     * Get product statistics for public display
     */
    public function stats()
    {
        $stats = [
            'total_products' => Product::where('is_available', true)
                ->where('status', 'active')
                ->count(),
            'frozen_products' => Product::where('is_available', true)
                ->where('status', 'active')
                ->where('is_frozen', true)
                ->count(),
            'categories_count' => \App\Models\Category::has('products')->count(),
            'featured_count' => Product::where('is_available', true)
                ->where('status', 'active')
                ->where('featured', true)
                ->count(),
        ];

        return response()->json($stats);
    }
}
