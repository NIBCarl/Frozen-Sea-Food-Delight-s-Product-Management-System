<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'province',
        'region',
        'base_shipping_rate',
        'estimated_delivery_days',
        'is_active',
        'requires_sea_transport',
        'delivery_notes',
    ];

    protected $casts = [
        'base_shipping_rate' => 'decimal:2',
        'estimated_delivery_days' => 'integer',
        'is_active' => 'boolean',
        'requires_sea_transport' => 'boolean',
    ];

    /**
     * Get orders for this shipping zone
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope for active zones only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for zones requiring sea transport
     */
    public function scopeRequiresSeaTransport($query)
    {
        return $query->where('requires_sea_transport', true);
    }

    /**
     * Scope for zones by province
     */
    public function scopeByProvince($query, string $province)
    {
        return $query->where('province', $province);
    }

    /**
     * Get estimated delivery date from now
     */
    public function getEstimatedDeliveryDate()
    {
        return now()->addDays($this->estimated_delivery_days);
    }

    /**
     * Check if this is a Cebu zone (local/free shipping)
     */
    public function isCebuZone(): bool
    {
        return $this->province === 'Cebu' || $this->base_shipping_rate == 0;
    }

    /**
     * Check if this is a Surigao zone
     */
    public function isSurigaoZone(): bool
    {
        return str_contains($this->province, 'Surigao');
    }
}
