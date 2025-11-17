<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use Illuminate\Http\Request;

class ShippingZoneController extends Controller
{
    /**
     * Get all active shipping zones
     */
    public function index()
    {
        $zones = ShippingZone::active()
            ->orderBy('province')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $zones
        ]);
    }

    /**
     * Get shipping zones by province
     */
    public function byProvince(Request $request, string $province)
    {
        $zones = ShippingZone::active()
            ->byProvince($province)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $zones
        ]);
    }

    /**
     * Calculate shipping cost for a zone
     */
    public function calculateShipping(Request $request)
    {
        $request->validate([
            'shipping_zone_id' => 'required|exists:shipping_zones,id',
            'subtotal' => 'required|numeric|min:0'
        ]);

        $zone = ShippingZone::findOrFail($request->shipping_zone_id);

        if (!$zone->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Selected shipping zone is not available'
            ], 400);
        }

        $shippingCost = $zone->base_shipping_rate;
        $total = $request->subtotal + $shippingCost;
        $estimatedDeliveryDate = $zone->getEstimatedDeliveryDate();

        return response()->json([
            'success' => true,
            'data' => [
                'zone' => $zone,
                'shipping_cost' => $shippingCost,
                'subtotal' => $request->subtotal,
                'total' => $total,
                'estimated_delivery_date' => $estimatedDeliveryDate->format('Y-m-d'),
                'estimated_delivery_days' => $zone->estimated_delivery_days,
            ]
        ]);
    }

    /**
     * Get shipping zone details
     */
    public function show(ShippingZone $shippingZone)
    {
        return response()->json([
            'success' => true,
            'data' => $shippingZone
        ]);
    }

    /**
     * Get Cebu zones (local shipping)
     */
    public function cebuZones()
    {
        $zones = ShippingZone::active()
            ->where('province', 'Cebu')
            ->orderBy('base_shipping_rate')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $zones
        ]);
    }

    /**
     * Get Surigao zones (inter-island shipping)
     */
    public function surigaoZones()
    {
        $zones = ShippingZone::active()
            ->where(function($query) {
                $query->where('province', 'like', '%Surigao%')
                      ->orWhere('province', 'Dinagat Islands')
                      ->orWhere('province', 'like', '%Agusan%');
            })
            ->orderBy('estimated_delivery_days')
            ->orderBy('base_shipping_rate')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $zones
        ]);
    }
}
