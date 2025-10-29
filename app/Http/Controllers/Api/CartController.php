<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Get current cart items from session
     */
    public function index(Request $request)
    {
        $cart = session()->get('cart', []);
        
        // Load product details for cart items
        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = Product::with('images')->find($productId);
            
            if ($product && $product->is_available) {
                $subtotal = $product->price * $quantity;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
                $total += $subtotal;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cartItems,
                'total' => $total,
                'item_count' => count($cartItems)
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

        $product = Product::find($request->product_id);

        // Validate product availability
        if (!$product->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Product is not available'
            ], 400);
        }

        // Check stock
        $cart = session()->get('cart', []);
        $currentQuantity = $cart[$request->product_id] ?? 0;
        $newQuantity = $currentQuantity + $request->quantity;

        if ($newQuantity > $product->stock_quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'available_stock' => $product->stock_quantity
            ], 400);
        }

        // Add to cart
        $cart[$request->product_id] = $newQuantity;
        session()->put('cart', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'cart_count' => count($cart)
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

        // Update cart
        $cart = session()->get('cart', []);
        $cart[$productId] = $request->quantity;
        session()->put('cart', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated'
        ]);
    }

    /**
     * Remove item from cart
     */
    public function removeItem($productId)
    {
        $cart = session()->get('cart', []);
        
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            session()->put('cart', $cart);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'cart_count' => count($cart)
        ]);
    }

    /**
     * Clear entire cart
     */
    public function clear()
    {
        session()->forget('cart');

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }
}
