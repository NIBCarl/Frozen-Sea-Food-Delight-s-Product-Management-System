<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'primaryImage', 'images', 'creator'])
            ->orderBy('created_at', 'desc');

        // Suppliers can only see their own products
        if ($request->user() && $request->user()->isSupplier()) {
            $query->where('created_by', $request->user()->id);
        }

        // For public endpoints (no include_inactive flag) keep only active & available products
        if (!$request->boolean('include_inactive')) {
            $query->where('is_available', true)
                  ->where('status', 'active');
        } else {
            // Optional explicit status filter for admins
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
        }

        // Seafood-specific filters
        if ($request->filled('fish_type')) {
            $query->byFishType($request->fish_type);
        }

        if ($request->boolean('frozen_only')) {
            $query->frozen();
        }

        if ($request->filled('freshness_grade')) {
            $query->byGrade($request->freshness_grade);
        }

        if ($request->filled('origin_waters')) {
            $query->fromWaters($request->origin_waters);
        }

        if ($request->boolean('expiring_soon')) {
            $days = $request->input('expiring_days', 7);
            $query->expiringSoon($days);
        }

        if ($request->boolean('exclude_expired')) {
            $query->whereDate('expiration_date', '>=', now())
                  ->orWhereNull('expiration_date');
        }

        $perPage = $request->input('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'barcode' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,discontinued',
            'featured' => 'boolean',
            // Seafood-specific fields
            'catch_date' => 'nullable|date|before_or_equal:today',
            'expiration_date' => 'nullable|date|after:catch_date',
            'storage_temperature' => 'nullable|string|max:20',
            'fishing_method' => 'nullable|string|max:100',
            'origin_waters' => 'nullable|string|max:100',
            'processing_date' => 'nullable|date|after_or_equal:catch_date|before_or_equal:today',
            'is_frozen' => 'nullable|boolean',
            'fish_type' => 'nullable|string|max:100',
            'weight_kg' => 'nullable|numeric|min:0',
            'freshness_grade' => 'nullable|in:A,B,C',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);
        $data['created_by'] = auth()->id();

        // Ensure unique slug
        $count = 1;
        $originalSlug = $data['slug'];
        while (Product::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $count;
            $count++;
        }

        // Retry product creation up to 3 times in case of ID conflicts
        $maxRetries = 3;
        $lastException = null;
        
        for ($retry = 0; $retry < $maxRetries; $retry++) {
            try {
                // Generate a new product ID for each attempt
                $data['product_id'] = $this->generateProductId();
                
                $product = Product::create($data);

                return response()->json([
                    'message' => 'Product created successfully',
                    'data' => $product->load(['category', 'primaryImage'])
                ], 201);
                
            } catch (\Illuminate\Database\QueryException $e) {
                $lastException = $e;
                
                // If it's a duplicate key error, try again with a new ID
                if ($e->getCode() == 23000 && strpos($e->getMessage(), 'product_id') !== false) {
                    if ($retry < $maxRetries - 1) {
                        // Wait a small random time before retry to avoid race conditions
                        usleep(rand(10000, 50000)); // 10-50ms
                        continue;
                    }
                    
                    return response()->json([
                        'message' => 'Product creation failed after multiple attempts',
                        'errors' => [
                            'general' => ['Unable to generate a unique product ID. Please try again.']
                        ]
                    ], 422);
                }
                
                // For other database errors, don't retry
                break;
                
            } catch (\Exception $e) {
                $lastException = $e;
                break;
            }
        }
        
        // If we get here, all retries failed
        return response()->json([
            'message' => 'Product creation failed',
            'error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ], 500);
    }

    public function show(Product $product)
    {
        return response()->json($product->load(['category', 'images', 'creator', 'stockMovements.creator']));
    }

    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,discontinued',
            'featured' => 'boolean',
            // Seafood-specific fields
            'catch_date' => 'nullable|date|before_or_equal:today',
            'expiration_date' => 'nullable|date|after:catch_date',
            'storage_temperature' => 'nullable|string|max:20',
            'fishing_method' => 'nullable|string|max:100',
            'origin_waters' => 'nullable|string|max:100',
            'processing_date' => 'nullable|date|after_or_equal:catch_date|before_or_equal:today',
            'is_frozen' => 'nullable|boolean',
            'fish_type' => 'nullable|string|max:100',
            'weight_kg' => 'nullable|numeric|min:0',
            'freshness_grade' => 'nullable|in:A,B,C',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        if ($request->has('name') && $request->name !== $product->name) {
            $data['slug'] = Str::slug($request->name);
            
            // Ensure unique slug
            $count = 1;
            $originalSlug = $data['slug'];
            while (Product::where('slug', $data['slug'])->where('id', '!=', $product->id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product->load(['category', 'primaryImage'])
        ]);
    }

    public function destroy(Product $product)
    {
        // Delete all product images
        foreach ($product->images as $image) {
            \Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    public function uploadImages(Request $request, Product $product)
    {
        // Allow both a single file field named "image" and an array field named "images[]"
        $single = $request->hasFile('image');

        $rules = $single ? [
            'image' => 'required|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'alt_text' => 'nullable|string|max:255',
        ] : [
            'images' => 'required|array',
            'images.*' => 'file|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'alt_text' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            \Log::error('Image upload validation failed', [
                'errors' => $validator->errors(),
                'request_files' => $request->allFiles(),
                'has_image' => $request->hasFile('image'),
                'has_images' => $request->hasFile('images')
            ]);
            
            return response()->json([
                'message' => 'Image validation failed: ' . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $files = $single ? [$request->file('image')] : $request->file('images');
        $images = [];

        try {
            foreach ($files as $file) {
                $path = $file->store('products', 'public');
                $productImage = $product->images()->create([
                    'image_path' => $path,
                    'alt_text' => $request->alt_text,
                    'sort_order' => $product->images()->count(),
                    'is_primary' => $product->images()->count() === 0,
                ]);
                $images[] = $productImage;
            }
        } catch (\Exception $e) {
            \Log::error('Image storage failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to store image: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Images uploaded successfully',
            'data' => $images
        ], 201);
    }

    public function deleteImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            return response()->json([
                'message' => 'Image not found'
            ], 404);
        }

        \Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully'
        ]);
    }

    public function setPrimaryImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            return response()->json([
                'message' => 'Image not found'
            ], 404);
        }

        DB::transaction(function () use ($product, $image) {
            $product->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return response()->json([
            'message' => 'Primary image updated successfully',
            'data' => $image
        ]);
    }

    private function generateProductId(): string
    {
        $prefix = 'P';
        $maxAttempts = 10;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Method 1: Try sequential numbering for first few attempts
                if ($attempt <= 3) {
                    // Use a subquery to get the next number atomically
                    $result = DB::selectOne("
                        SELECT COALESCE(MAX(CAST(SUBSTRING(product_id, 2) AS UNSIGNED)), 0) + 1 as next_number 
                        FROM products 
                        WHERE product_id REGEXP '^P[0-9]+$'
                    ");
                    
                    $number = $result->next_number ?? 1;
                    $productId = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
                } else {
                    // Method 2: Use timestamp + random for uniqueness
                    $timestamp = now()->timestamp;
                    $random = rand(100, 999);
                    $uniqueNumber = ($timestamp % 999999) + $random;
                    $productId = $prefix . str_pad($uniqueNumber % 999999, 6, '0', STR_PAD_LEFT);
                }
                
                // Try to reserve this ID by checking if it exists
                $exists = Product::where('product_id', $productId)->exists();
                
                if (!$exists) {
                    return $productId;
                }
                
            } catch (\Exception $e) {
                // Continue to next attempt on any error
                continue;
            }
        }
        
        // Final fallback: Use UUID
        $uuid = str_replace('-', '', \Illuminate\Support\Str::uuid());
        return $prefix . strtoupper(substr($uuid, 0, 6));
    }
}
