<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\SmsGateway;

class DeliveryController extends Controller
{
    protected $smsGateway;

    public function __construct(SmsGateway $smsGateway)
    {
        $this->smsGateway = $smsGateway;
    }
    /**
     * Display a listing of deliveries
     */
    public function index(Request $request)
    {
        $query = Delivery::with(['order.customer', 'order.items.product', 'deliveryPersonnel']);

        // Delivery personnel sees only their assigned deliveries
        if ($request->user()->isDeliveryPersonnel()) {
            $query->where('delivery_personnel_id', $request->user()->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('scheduled_date', $request->date);
        }

        $deliveries = $query->orderBy('scheduled_date', 'asc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $deliveries
        ]);
    }

    /**
     * Create or update a delivery schedule (Admin only)
     */
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'delivery_personnel_id' => 'required|exists:users,id',
            'scheduled_date' => 'required|date',
            'delivery_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update or create delivery record (handles both new and existing deliveries)
            $delivery = Delivery::updateOrCreate(
                ['order_id' => $request->order_id],
                [
                    'delivery_personnel_id' => $request->delivery_personnel_id,
                    'scheduled_date' => $request->scheduled_date,
                    'delivery_notes' => $request->delivery_notes,
                    'status' => 'scheduled',
                ]
            );

            // Update order status to in_transit
            Order::find($request->order_id)->update(['status' => 'in_transit']);

            $delivery->load(['order', 'deliveryPersonnel']);

            return response()->json([
                'success' => true,
                'message' => 'Delivery assigned successfully',
                'data' => $delivery
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign delivery',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update delivery status (Delivery Personnel)
     */
    public function updateStatus(Request $request, Delivery $delivery)
    {
        // Check authorization
        if ($request->user()->isDeliveryPersonnel() && $delivery->delivery_personnel_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:scheduled,out_for_delivery,in_transit,delivered,failed',
            'delivery_notes' => 'nullable|string',
            'failure_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = ['status' => $request->status];

            if ($request->status === 'delivered') {
                $updateData['actual_delivery_datetime'] = now();
                // Update order status
                $delivery->order->update([
                    'status' => 'delivered',
                    'payment_status' => 'paid',
                    'actual_delivery_date' => now()
                ]);
            }

            if ($request->status === 'failed' && $request->failure_reason) {
                $updateData['failure_reason'] = $request->failure_reason;
            }

            if ($request->delivery_notes) {
                $updateData['delivery_notes'] = $request->delivery_notes;
            }

            $delivery->update($updateData);
            $delivery->load(['order', 'deliveryPersonnel']);

            // Send SMS notification
            try {
                $message = '';
                if ($request->status === 'out_for_delivery') {
                    $message = "Your order from Seafood Delight is out for delivery today!";
                } elseif ($request->status === 'delivered') {
                    // This message might be duplicate if OrderController also sends it, but DeliveryController updates model directly so OrderController logic won't run.
                    // So we MUST send it here.
                    $message = "Your Order #{$delivery->order->order_number} has been delivered. Thank you!";
                } elseif ($request->status === 'failed') {
                    $message = "Delivery attempt for Order #{$delivery->order->order_number} failed. Reason: " . ($request->failure_reason ?? 'Customer unavailable');
                }

                if ($message && $delivery->order->contact_number) {
                    $this->smsGateway->send($delivery->order->contact_number, $message);
                }
            } catch (\Exception $e) {
                // Log but don't fail the request
                \Log::error("Failed to send delivery SMS: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Delivery status updated',
                'data' => $delivery
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's deliveries for delivery personnel
     */
    public function todayDeliveries(Request $request)
    {
        if (!$request->user()->isDeliveryPersonnel()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Show today's and upcoming active deliveries (within next 7 days)
        // This is more practical for delivery personnel to prepare ahead
        $deliveries = Delivery::with(['order.customer', 'order.items.product'])
            ->where('delivery_personnel_id', $request->user()->id)
            ->where('scheduled_date', '>=', today())
            ->where('scheduled_date', '<=', today()->addDays(7))
            ->whereIn('status', ['scheduled', 'out_for_delivery', 'in_transit'])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deliveries
        ]);
    }

    /**
     * Delivery history (delivered & failed) for delivery personnel
     */
    public function historyDeliveries(Request $request)
    {
        if (!$request->user()->isDeliveryPersonnel()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $deliveries = Delivery::with(['order.customer', 'order.items.product'])
            ->where('delivery_personnel_id', $request->user()->id)
            ->whereIn('status', ['delivered', 'failed'])
            ->orderBy('scheduled_date', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $deliveries
        ]);
    }

    /**
     * Get all delivery statistics for delivery personnel
     */
    public function todayStatistics(Request $request)
    {
        if (!$request->user()->isDeliveryPersonnel()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Get TODAY's deliveries by status for this user
        $todayDeliveries = Delivery::where('delivery_personnel_id', $request->user()->id)
            ->whereDate('scheduled_date', today())
            ->get();

        $statistics = [
            'scheduled' => $todayDeliveries->where('status', 'scheduled')->count(),
            'out_for_delivery' => $todayDeliveries->where('status', 'out_for_delivery')->count(),
            'in_transit' => $todayDeliveries->where('status', 'in_transit')->count(),
            'delivered' => $todayDeliveries->where('status', 'delivered')->count(),
            'failed' => $todayDeliveries->where('status', 'failed')->count(),
            'total' => $todayDeliveries->count()
        ];

        // Debug logging
        \Log::info('Today Delivery Statistics:', [
            'date' => today()->toDateString(),
            'total_deliveries' => $todayDeliveries->count(),
            'statistics' => $statistics
        ]);

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
