<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{
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
     * Create a new delivery schedule (Admin only)
     */
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'delivery_personnel_id' => 'nullable|exists:users,id',
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
            $delivery = Delivery::create($request->all());

            // Update order status to in_transit
            Order::find($request->order_id)->update(['status' => 'in_transit']);

            $delivery->load(['order', 'deliveryPersonnel']);

            return response()->json([
                'success' => true,
                'message' => 'Delivery scheduled successfully',
                'data' => $delivery
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create delivery',
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

        $deliveries = Delivery::with(['order.customer', 'order.items.product'])
            ->where('delivery_personnel_id', $request->user()->id)
            ->today()
            ->whereIn('status', ['scheduled', 'out_for_delivery', 'in_transit'])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deliveries
        ]);
    }
}
