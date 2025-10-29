<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShipmentController extends Controller
{
    /**
     * Display a listing of shipments
     */
    public function index(Request $request)
    {
        $query = Shipment::with(['supplier', 'items.product', 'confirmedBy']);

        // Suppliers see only their shipments
        if ($request->user()->isSupplier()) {
            $query->where('supplier_id', $request->user()->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $shipments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $shipments
        ]);
    }

    /**
     * Log a new shipment (Supplier only)
     */
    public function store(Request $request)
    {
        if (!$request->user()->isSupplier()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'expected_arrival_date' => 'required|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create shipment
            $shipment = Shipment::create([
                'supplier_id' => $request->user()->id,
                'expected_arrival_date' => $request->expected_arrival_date,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Create shipment items
            foreach ($request->items as $item) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            // Update shipment status to in_transit
            $shipment->update(['status' => 'in_transit']);

            DB::commit();

            $shipment->load(['items.product', 'supplier']);

            return response()->json([
                'success' => true,
                'message' => 'Shipment logged successfully',
                'data' => $shipment
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to log shipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm shipment arrival (Admin only)
     */
    public function confirmArrival(Request $request, Shipment $shipment)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$shipment->canBeConfirmed()) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment must be in arrived status to confirm'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Update inventory for each item
            foreach ($shipment->items as $item) {
                $product = Product::find($item->product_id);
                $product->increment('stock_quantity', $item->quantity);

                // Log stock movement
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reference' => "SHIPMENT-{$shipment->shipment_number}",
                    'created_by' => $request->user()->id,
                ]);
            }

            // Update shipment
            $shipment->update([
                'status' => 'confirmed',
                'confirmed_by' => $request->user()->id,
                'confirmed_at' => now(),
                'actual_arrival_date' => now(),
            ]);

            DB::commit();

            $shipment->load(['items.product', 'supplier', 'confirmedBy']);

            return response()->json([
                'success' => true,
                'message' => 'Shipment confirmed and inventory updated',
                'data' => $shipment
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm shipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark shipment as arrived (Admin only)
     */
    public function markAsArrived(Request $request, Shipment $shipment)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $shipment->update([
                'status' => 'arrived',
                'actual_arrival_date' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipment marked as arrived',
                'data' => $shipment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update shipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
