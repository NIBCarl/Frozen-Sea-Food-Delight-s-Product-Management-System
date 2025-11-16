<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_personnel_id',
        'scheduled_date',
        'actual_delivery_datetime',
        'status',
        'delivery_notes',
        'failure_reason',
        'delivery_confirmation_photo',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'actual_delivery_datetime' => 'datetime',
    ];

    /**
     * Get the order for this delivery
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the delivery personnel assigned
     */
    public function deliveryPersonnel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_personnel_id');
    }

    /**
     * Scope for scheduled deliveries
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope for out for delivery
     */
    public function scopeOutForDelivery($query)
    {
        return $query->where('status', 'out_for_delivery');
    }

    /**
     * Scope for delivered
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope for today's deliveries
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    /**
     * Check if delivery is pending
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if delivery is completed
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }
}
