<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockMovementController extends Controller
{
    public function index()
    {
        $movements = StockMovement::with(['product', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($movements);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::findOrFail($request->product_id);
        
        DB::transaction(function () use ($request, $product) {
            $movement = StockMovement::create([
                'product_id' => $request->product_id,
                'type' => $request->type,
                'quantity' => $request->quantity,
                'reference' => $request->reference,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // Update product stock quantity
            $newQuantity = $product->stock_quantity;
            switch ($request->type) {
                case 'in':
                    $newQuantity += $request->quantity;
                    break;
                case 'out':
                    $newQuantity -= $request->quantity;
                    break;
                case 'adjustment':
                    $newQuantity = $request->quantity;
                    break;
            }

            $product->update(['stock_quantity' => max(0, $newQuantity)]);
        });

        return response()->json([
            'message' => 'Stock movement recorded successfully',
            'data' => $movement->load(['product', 'creator'])
        ], 201);
    }

    public function show(StockMovement $stockMovement)
    {
        return response()->json($stockMovement->load(['product', 'creator']));
    }

    public function productHistory(Product $product)
    {
        $movements = $product->stockMovements()
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($movements);
    }

    public function stockAlerts()
    {
        $lowStockProducts = Product::with(['category', 'primaryImage'])
            ->whereRaw('stock_quantity <= min_stock_level')
            ->orderBy('stock_quantity', 'asc')
            ->get();

        return response()->json([
            'data' => $lowStockProducts,
            'count' => $lowStockProducts->count()
        ]);
    }
}
