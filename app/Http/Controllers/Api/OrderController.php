<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\SmsGateway;

class OrderController extends Controller
{
    protected $smsGateway;

    public function __construct(SmsGateway $smsGateway)
    {
        $this->smsGateway = $smsGateway;
    }


    /**
     * Display a listing of orders
     * Admin: all orders
     * Customer: own orders only
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items.product', 'delivery.deliveryPersonnel', 'shippingZone']);

        // Filter by user role
        if ($request->user()->hasRole('customer')) {
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
        Log::info('Order creation started', $request->all());

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|string',
            'contact_number' => 'required|string',
            'preferred_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'payment_method' => 'required|in:cash_on_delivery,gcash',
            'payment_receipt_path' => 'required_if:payment_method,gcash|nullable|string',
            'shipping_zone_id' => 'required|exists:shipping_zones,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Log::info('Starting DB transaction');
            DB::beginTransaction();

            // Calculate total and validate stock
            $totalAmount = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check if product is available and active
                if (!$product->is_available || in_array($product->status, ['inactive','discontinued'])) {
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

            // Get shipping zone and calculate shipping cost
            $shippingZone = ShippingZone::findOrFail($request->shipping_zone_id);
            
            if (!$shippingZone->is_active) {
                throw new \Exception("Selected shipping zone is not available");
            }

            $shippingCost = $shippingZone->base_shipping_rate;
            $totalAmountWithShipping = $totalAmount + $shippingCost;

            // Determine payment status based on payment method
            $paymentStatus = $request->payment_method === 'gcash' ? 'verification_pending' : 'pending';

            // Create order
            $order = Order::create([
                'user_id' => $request->user()->id,
                'delivery_address' => $request->delivery_address,
                'contact_number' => $request->contact_number,
                'preferred_delivery_date' => $request->preferred_delivery_date ?? now(),
                'status' => 'pending',
                'total_amount' => $totalAmountWithShipping,
                'shipping_zone_id' => $shippingZone->id,
                'shipping_cost' => $shippingCost,
                'payment_method' => $request->payment_method,
                'payment_status' => $paymentStatus,
                'payment_receipt_path' => $request->payment_receipt_path,
                'notes' => $request->notes,
            ]);

            // Prepare bulk insert arrays
            $itemRows = [];
            $movementRows = [];
            foreach ($orderItems as $item) {
                $itemRows[] = [
                    'order_id'   => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                    'subtotal'   => $item['subtotal'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Decrement stock (still individual but lightweight UPDATE)
                $item['product']->decrement('stock_quantity', $item['quantity']);

                $movementRows[] = [
                    'product_id' => $item['product']->id,
                    'type'       => 'out',
                    'quantity'   => $item['quantity'],
                    'reference'  => "ORDER-{$order->order_number}",
                    'created_by' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert order items & stock movements
            OrderItem::insert($itemRows);
            StockMovement::insert($movementRows);

            // Create delivery record for the order (only if one doesn't exist)
            if (!\App\Models\Delivery::where('order_id', $order->id)->exists()) {
                $scheduledDate = $request->preferred_delivery_date 
                    ? \Carbon\Carbon::parse($request->preferred_delivery_date)
                    : now()->addDay(); // Default to tomorrow if no date specified

                \App\Models\Delivery::create([
                    'order_id' => $order->id,
                    'scheduled_date' => $scheduledDate,
                    'status' => 'scheduled',
                    'delivery_personnel_id' => null, // Will be assigned later by admin
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
            Log::error('Order creation failed: ' . $e->getMessage());
            
            // Check for business logic errors (stock, expiration, availability)
            $msg = $e->getMessage();
            if (str_contains($msg, 'expired') || 
                str_contains($msg, 'Insufficient stock') || 
                str_contains($msg, 'not available')) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $msg
            ], 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show(Request $request, Order $order)
    {
        // Check if user can view this order
        if ($request->user()->hasRole('customer') && $order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $order->load(['customer', 'items.product.images', 'delivery.deliveryPersonnel', 'shippingZone']);

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
        if (!$request->user()->hasRole('admin')) {
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

            // Send SMS notification
            try {
                $message = '';
                switch ($request->status) {
                    case 'processing':
                        $message = "Your Order #{$order->order_number} is now being processed.";
                        break;
                    case 'in_transit':
                        $message = "Your Order #{$order->order_number} is on its way!";
                        break;
                    case 'delivered':
                        $message = "Your Order #{$order->order_number} has been delivered. Thank you!";
                        break;
                }

                if ($message && $order->contact_number) {
                    $this->smsGateway->send($order->contact_number, $message);
                }
            } catch (\Exception $e) {
                Log::error("Failed to send SMS for order {$order->order_number}: " . $e->getMessage());
            }

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
        if ($request->user()->hasRole('customer') && $order->user_id !== $request->user()->id) {
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
                // Get product even if it was soft deleted
                $product = Product::withTrashed()->find($item->product_id);

                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);

                    // Log stock movement
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'reference' => "ORDER-CANCELLED-{$order->order_number}",
                        'created_by' => $request->user()->id,
                    ]);
                }
            }

            // Update order status
            $order->update(['status' => 'cancelled']);

            DB::commit();

            // Send SMS notification
            try {
                if ($order->contact_number) {
                    $this->smsGateway->send($order->contact_number, "Your Order #{$order->order_number} has been cancelled.");
                }
            } catch (\Exception $e) {
                Log::error("Failed to send cancellation SMS for order {$order->order_number}: " . $e->getMessage());
            }

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

    /**
     * Verify GCash payment (Admin only)
     */
    public function verifyPayment(Request $request, Order $order)
    {
        // Check if user is admin
        if (!$request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
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
            if ($request->action === 'approve') {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_verified_at' => now(),
                    'payment_verified_by' => $request->user()->id,
                ]);
                $message = 'Payment verified successfully';
            } else {
                $order->update([
                    'payment_status' => 'verification_failed',
                    'notes' => $order->notes . "\n\nPayment verification failed: " . ($request->notes ?? 'No reason provided'),
                ]);
                $message = 'Payment verification rejected';
            }
            
            // Send SMS
            try {
                if ($order->contact_number) {
                    $smsMsg = $request->action === 'approve' 
                        ? "Payment for Order #{$order->order_number} verified. We are processing it now."
                        : "Payment for Order #{$order->order_number} was rejected. Reason: " . ($request->notes ?? 'Contact support');
                    
                    $this->smsGateway->send($order->contact_number, $smsMsg);
                }
            } catch (\Exception $e) {
                Log::error("Failed to send payment SMS: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $order->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
