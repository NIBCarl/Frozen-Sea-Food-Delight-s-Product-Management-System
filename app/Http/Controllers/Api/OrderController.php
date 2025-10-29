<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     * Admin: all orders
     * Customer: own orders only
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items.product', 'delivery']);

        // Filter by user role
        if ($request->user()->isCustomer()) {
            $query->where('user_id', $request->user()->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sort by created_at descending (newest first)
        $query->orderBy('created_at', 'desc');

        $orders = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created order
     * Customer only
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|string',
            'contact_number' => 'required|string',
            'preferred_delivery_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate total and validate stock
            $totalAmount = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check if product is available
                if (!$product->is_available) {
                    throw new \Exception("Product {$product->name} is not available");
                }

                // Check if product is expired
                if ($product->isExpired()) {
                    throw new \Exception("Product {$product->name} has expired");
                }

                // Check stock availability
                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock_quantity}");
                }

                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            // Create order
            $order = Order::create([
                'user_id' => $request->user()->id,
                'delivery_address' => $request->delivery_address,
                'contact_number' => $request->contact_number,
                'preferred_delivery_date' => $request->preferred_delivery_date ?? now()->addDays(2),
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Create order items and update stock
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Update product stock
                $item['product']->decrement('stock_quantity', $item['quantity']);

                // Log stock movement
                StockMovement::create([
                    'product_id' => $item['product']->id,
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'reference' => "ORDER-{$order->order_number}",
                    'created_by' => $request->user()->id,
                ]);
            }

            DB::commit();

            // Load relationships
            $order->load(['items.product', 'customer']);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show(Request $request, Order $order)
    {
        // Check if user can view this order
        if ($request->user()->isCustomer() && $order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $order->load(['customer', 'items.product.images', 'delivery.deliveryPersonnel']);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update order status
     * Admin only
     */
    public function updateStatus(Request $request, Order $order)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,in_transit,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order->update([
                'status' => $request->status
            ]);

            // If delivered, update actual delivery date
            if ($request->status === 'delivered') {
                $order->update([
                    'actual_delivery_date' => now(),
                    'payment_status' => 'paid'
                ]);

                if ($order->delivery) {
                    $order->delivery->update([
                        'status' => 'delivered',
                        'actual_delivery_datetime' => now()
                    ]);
                }
            }

            $order->load(['customer', 'items.product', 'delivery']);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an order
     * Customer can cancel pending/processing orders
     */
    public function destroy(Request $request, Order $order)
    {
        // Check if user can cancel this order
        if ($request->user()->isCustomer() && $order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if order can be cancelled
        if (!$order->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be cancelled'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Restore stock for each item
            foreach ($order->items as $item) {
                $item->product->increment('stock_quantity', $item->quantity);

                // Log stock movement
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reference' => "ORDER-CANCELLED-{$order->order_number}",
                    'created_by' => $request->user()->id,
                ]);
            }

            // Update order status
            $order->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
