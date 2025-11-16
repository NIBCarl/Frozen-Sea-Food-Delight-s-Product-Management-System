<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'delivery_address',
        'contact_number',
        'preferred_delivery_date',
        'actual_delivery_date',
        'status',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_receipt_path',
        'payment_verified_at',
        'payment_verified_by',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'preferred_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'payment_verified_at' => 'datetime',
    ];

    /**
     * Generate unique order number
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'ORD-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    /**
     * Get the customer who placed the order
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all items in this order
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the delivery for this order
     */
    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    /**
     * Get the admin who verified the payment
     */
    public function paymentVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processing orders
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for in transit orders
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    /**
     * Scope for delivered orders
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Check if order is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Check if order is using GCash payment
     */
    public function isGCashPayment(): bool
    {
        return $this->payment_method === 'gcash';
    }

    /**
     * Check if payment is pending verification
     */
    public function isPaymentVerificationPending(): bool
    {
        return $this->payment_status === 'verification_pending';
    }

    /**
     * Check if payment verification failed
     */
    public function isPaymentVerificationFailed(): bool
    {
        return $this->payment_status === 'verification_failed';
    }

    /**
     * Check if payment is verified/paid
     */
    public function isPaymentVerified(): bool
    {
        return $this->payment_status === 'paid';
    }
}
