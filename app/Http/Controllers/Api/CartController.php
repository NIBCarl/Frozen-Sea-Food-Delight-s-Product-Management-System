<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Get current cart items for authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get cart items with optimized query
        $cartItems = Cart::with(['product:id,name,price,slug,weight,is_available', 'product.primaryImage:id,product_id,image_path'])
            ->where('user_id', $user->id)
            ->get()
            ->filter(function($cartItem) {
                return $cartItem->product && $cartItem->product->is_available;
            });

        $total = $cartItems->sum('subtotal');

        $formattedItems = $cartItems->map(function($item) {
            return [
                'id' => $item->id,
                'product' => $item->product,
                'quantity' => $item->quantity,
                'price_at_time' => $item->price_at_time,
                'subtotal' => $item->subtotal,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $formattedItems,
                'total' => $total,
                'item_count' => $cartItems->count()
            ]
        ]);
    }

    /**
     * Add item to cart
     */
    public function addItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $product = Product::find($request->product_id);

        // Validate product availability / status
        if (!$product->is_available || in_array($product->status, ['inactive','discontinued'])) {
            return response()->json([
                'success' => false,
                'message' => 'Product is not available'
            ], 400);
        }

        // Check existing cart item
        $existingCartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        $currentQuantity = $existingCartItem ? $existingCartItem->quantity : 0;
        $newQuantity = $currentQuantity + $request->quantity;

        // Check stock
        if ($newQuantity > $product->stock_quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'available_stock' => $product->stock_quantity,
                'current_in_cart' => $currentQuantity
            ], 400);
        }

        // Add or update cart item
        if ($existingCartItem) {
            $existingCartItem->update([
                'quantity' => $newQuantity,
                'price_at_time' => $product->price // Update price to current
            ]);
            $cartItem = $existingCartItem;
        } else {
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'price_at_time' => $product->price
            ]);
        }

        // Get updated cart count
        $cartCount = Cart::where('user_id', $user->id)->sum('quantity');

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart successfully',
            'cart_count' => $cartCount
        ]);
    }

    /**
     * Update cart item quantity
     */
    public function updateItem(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Check stock
        if ($request->quantity > $product->stock_quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'available_stock' => $product->stock_quantity
            ], 400);
        }

        // Find and update cart item in database
        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        $cartItem->update([
            'quantity' => $request->quantity,
            'price_at_time' => $product->price // Update to current price
        ]);

        // Get updated cart count
        $cartCount = Cart::where('user_id', $user->id)->sum('quantity');

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'cart_count' => $cartCount
        ]);
    }

    /**
     * Remove item from cart
     */
    public function removeItem($productId)
    {
        $user = Auth::user();
        
        // Find and delete cart item from database
        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();
        
        if ($cartItem) {
            $cartItem->delete();
        }

        // Get updated cart count
        $cartCount = Cart::where('user_id', $user->id)->sum('quantity');

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'cart_count' => $cartCount
        ]);
    }

    /**
     * Clear entire cart
     */
    public function clear()
    {
        $user = Auth::user();

        // Delete all cart records for this user
        \App\Models\Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared',
            'cart_count' => 0
        ]);
    }
}
