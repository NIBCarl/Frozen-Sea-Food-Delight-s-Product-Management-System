<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PaymentReceiptController extends Controller
{
    /**
     * Upload payment receipt
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receipt' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file upload',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('receipt');
            $filename = 'payment_receipt_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Store in public/storage/payment_receipts directory
            $path = $file->storeAs('payment_receipts', $filename, 'public');

            return response()->json([
                'success' => true,
                'message' => 'Receipt uploaded successfully',
                'data' => [
                    'path' => $path,
                    'url' => Storage::url($path)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete payment receipt
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if (Storage::disk('public')->exists($request->path)) {
                Storage::disk('public')->delete($request->path);
            }

            return response()->json([
                'success' => true,
                'message' => 'Receipt deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
