<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierOrderController extends Controller
{
    /**
     * Get orders containing supplier's products
     */
    public function index(Request $request)
    {
        if (!$request->user()->isSupplier()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Get supplier's product IDs
        $supplierProductIds = Product::where('created_by', $request->user()->id)
            ->pluck('id')
            ->toArray();

        if (empty($supplierProductIds)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'data' => [],
                    'total' => 0,
                    'current_page' => 1,
                    'last_page' => 1
                ]
            ]);
        }

        // Get orders that contain supplier's products
        $query = Order::with(['customer', 'items.product', 'delivery.deliveryPersonnel'])
            ->whereHas('items', function ($q) use ($supplierProductIds) {
                $q->whereIn('product_id', $supplierProductIds);
            });

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status if provided
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        // Filter order items to show only supplier's products
        $orders->getCollection()->transform(function ($order) use ($supplierProductIds) {
            $order->items = $order->items->filter(function ($item) use ($supplierProductIds) {
                return in_array($item->product_id, $supplierProductIds);
            });
            
            // Calculate supplier's portion of the order
            $supplierTotal = $order->items->sum('subtotal');
            $order->supplier_total = $supplierTotal;
            
            return $order;
        });

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get supplier order statistics
     */
    public function statistics(Request $request)
    {
        if (!$request->user()->isSupplier()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Get supplier's product IDs
        $supplierProductIds = Product::where('created_by', $request->user()->id)
            ->pluck('id')
            ->toArray();

        if (empty($supplierProductIds)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_orders' => 0,
                    'pending_orders' => 0,
                    'processing_orders' => 0,
                    'completed_orders' => 0,
                    'total_revenue' => 0,
                    'total_items_sold' => 0
                ]
            ]);
        }

        // Get orders containing supplier's products
        $orders = Order::whereHas('items', function ($q) use ($supplierProductIds) {
            $q->whereIn('product_id', $supplierProductIds);
        })->get();

        // Calculate statistics
        $statistics = [
            'total_orders' => $orders->count(),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'processing_orders' => $orders->where('status', 'processing')->count(),
            'completed_orders' => $orders->whereIn('status', ['delivered'])->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
        ];

        // Calculate revenue and items sold from supplier's products only
        $supplierItems = OrderItem::whereIn('product_id', $supplierProductIds)
            ->whereHas('order', function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            })
            ->get();

        $statistics['total_revenue'] = $supplierItems->sum('subtotal');
        $statistics['total_items_sold'] = $supplierItems->sum('quantity');

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get detailed view of a specific order (supplier's items only)
     */
    public function show(Request $request, Order $order)
    {
        if (!$request->user()->isSupplier()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Get supplier's product IDs
        $supplierProductIds = Product::where('created_by', $request->user()->id)
            ->pluck('id')
            ->toArray();

        // Check if this order contains supplier's products
        $hasSupplierProducts = $order->items()
            ->whereIn('product_id', $supplierProductIds)
            ->exists();

        if (!$hasSupplierProducts) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or does not contain your products'
            ], 404);
        }

        // Load order with relationships
        $order->load(['customer', 'items.product', 'delivery.deliveryPersonnel']);

        // Filter items to show only supplier's products
        $order->items = $order->items->filter(function ($item) use ($supplierProductIds) {
            return in_array($item->product_id, $supplierProductIds);
        });

        // Calculate supplier's portion
        $supplierTotal = $order->items->sum('subtotal');
        $order->supplier_total = $supplierTotal;

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Get recent orders summary for dashboard
     */
    public function recentOrders(Request $request)
    {
        if (!$request->user()->isSupplier()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Get supplier's product IDs
        $supplierProductIds = Product::where('created_by', $request->user()->id)
            ->pluck('id')
            ->toArray();

        if (empty($supplierProductIds)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Get recent orders (last 10)
        $recentOrders = Order::with(['customer', 'items.product'])
            ->whereHas('items', function ($q) use ($supplierProductIds) {
                $q->whereIn('product_id', $supplierProductIds);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Transform to include only supplier's items and totals
        $recentOrders->transform(function ($order) use ($supplierProductIds) {
            $supplierItems = $order->items->filter(function ($item) use ($supplierProductIds) {
                return in_array($item->product_id, $supplierProductIds);
            });
            
            $order->supplier_items = $supplierItems;
            $order->supplier_total = $supplierItems->sum('subtotal');
            $order->supplier_item_count = $supplierItems->sum('quantity');
            
            return $order;
        });

        return response()->json([
            'success' => true,
            'data' => $recentOrders
        ]);
    }

    /**
     * Mark supplier's products as ready for an order
     */
    public function markAsReady(Request $request, Order $order)
    {
        if (!$request->user()->isSupplier()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Get supplier's product IDs
        $supplierProductIds = Product::where('created_by', $request->user()->id)
            ->pluck('id')
            ->toArray();

        // Check if this order contains supplier's products
        $hasSupplierProducts = $order->items()
            ->whereIn('product_id', $supplierProductIds)
            ->exists();

        if (!$hasSupplierProducts) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or does not contain your products'
            ], 404);
        }

        // Check if order can be marked as ready
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Order must be in pending status to mark as ready'
            ], 400);
        }

        try {
            // Add a note to the order indicating supplier readiness
            $supplierName = $request->user()->name;
            $currentNotes = $order->notes ?? '';
            $readyNote = "\n\n[" . now()->format('Y-m-d H:i:s') . "] Supplier ({$supplierName}) marked their products as ready for processing.";
            
            $order->update([
                'notes' => $currentNotes . $readyNote
            ]);

            // In a more advanced system, you might:
            // - Send notification to admin
            // - Update a separate supplier_readiness table
            // - Trigger workflow automation

            return response()->json([
                'success' => true,
                'message' => 'Products marked as ready for processing',
                'data' => $order->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark products as ready',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Report an issue with an order
     */
    public function reportIssue(Request $request, Order $order)
    {
        if (!$request->user()->isSupplier()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = \Validator::make($request->all(), [
            'issue_type' => 'required|in:stock_shortage,quality_issue,delivery_delay,other',
            'description' => 'required|string|max:1000',
            'severity' => 'required|in:low,medium,high,critical'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get supplier's product IDs
        $supplierProductIds = Product::where('created_by', $request->user()->id)
            ->pluck('id')
            ->toArray();

        // Check if this order contains supplier's products
        $hasSupplierProducts = $order->items()
            ->whereIn('product_id', $supplierProductIds)
            ->exists();

        if (!$hasSupplierProducts) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or does not contain your products'
            ], 404);
        }

        try {
            // Add issue report to order notes
            $supplierName = $request->user()->name;
            $currentNotes = $order->notes ?? '';
            $issueNote = "\n\n[" . now()->format('Y-m-d H:i:s') . "] ISSUE REPORTED by {$supplierName}:\n";
            $issueNote .= "Type: " . ucfirst(str_replace('_', ' ', $request->issue_type)) . "\n";
            $issueNote .= "Severity: " . ucfirst($request->severity) . "\n";
            $issueNote .= "Description: " . $request->description;
            
            $order->update([
                'notes' => $currentNotes . $issueNote
            ]);

            // In a more advanced system, you might:
            // - Create a separate issues table
            // - Send immediate notification to admin
            // - Create a support ticket
            // - Log in audit trail

            return response()->json([
                'success' => true,
                'message' => 'Issue reported successfully',
                'data' => $order->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to report issue',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
