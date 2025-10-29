<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_number',
        'supplier_id',
        'expected_arrival_date',
        'actual_arrival_date',
        'status',
        'notes',
        'shipping_documents',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'expected_arrival_date' => 'date',
        'actual_arrival_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Generate unique shipment number
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($shipment) {
            if (!$shipment->shipment_number) {
                $shipment->shipment_number = 'SHIP-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    /**
     * Get the supplier who sent this shipment
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    /**
     * Get the admin who confirmed this shipment
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get all items in this shipment
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * Scope for pending shipments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for in transit shipments
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    /**
     * Scope for arrived shipments
     */
    public function scopeArrived($query)
    {
        return $query->where('status', 'arrived');
    }

    /**
     * Check if shipment can be confirmed
     */
    public function canBeConfirmed(): bool
    {
        return $this->status === 'arrived';
    }
}
