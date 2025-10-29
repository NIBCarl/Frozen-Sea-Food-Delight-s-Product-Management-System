<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with(['parent', 'children'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        // Ensure unique slug
        $count = 1;
        $originalSlug = $data['slug'];
        while (Category::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $count;
            $count++;
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $data['image'] = $path;
        }

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category->load(['parent', 'children'])
        ], 201);
    }

    public function show(Category $category)
    {
        return response()->json($category->load(['parent', 'children', 'products']));
    }

    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        if ($request->has('name') && $request->name !== $category->name) {
            $data['slug'] = Str::slug($request->name);
            
            // Ensure unique slug
            $count = 1;
            $originalSlug = $data['slug'];
            while (Category::where('slug', $data['slug'])->where('id', '!=', $category->id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image) {
                \Storage::disk('public')->delete($category->image);
            }
            
            $path = $request->file('image')->store('categories', 'public');
            $data['image'] = $path;
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category->load(['parent', 'children'])
        ]);
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with products'
            ], 422);
        }

        if ($category->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories'
            ], 422);
        }

        // Delete category image
        if ($category->image) {
            \Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }

    public function products(Category $category)
    {
        $products = $category->products()->with(['category', 'primaryImage'])->paginate(15);
        return response()->json($products);
    }
}
