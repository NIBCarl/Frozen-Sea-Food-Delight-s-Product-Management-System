<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'description',
        'category_id',
        'price',
        'cost_price',
        'stock_quantity',
        'min_stock_level',
        'expiration_date',
        'is_available',
        'sku',
        'barcode',
        'weight',
        'dimensions',
        'status',
        'featured',
        'created_by',
        // Seafood-specific attributes
        'catch_date',
        'storage_temperature',
        'fishing_method',
        'origin_waters',
        'processing_date',
        'is_frozen',
        'fish_type',
        'weight_kg',
        'freshness_grade',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'weight' => 'decimal:2',
        'featured' => 'boolean',
        'status' => 'string',
        'expiration_date' => 'date',
        'is_available' => 'boolean',
        // Seafood-specific casts
        'catch_date' => 'date',
        'processing_date' => 'date',
        'is_frozen' => 'boolean',
        'weight_kg' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= min_stock_level');
    }

    public function isLowStock()
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipmentItems()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->whereNotNull('expiration_date')
                    ->whereDate('expiration_date', '<=', now()->addDays($days))
                    ->whereDate('expiration_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiration_date')
                    ->whereDate('expiration_date', '<', now());
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
                    ->where('stock_quantity', '>', 0);
    }

    public function isExpiringSoon($days = 7): bool
    {
        if (!$this->expiration_date) {
            return false;
        }
        
        return $this->expiration_date->diffInDays(now()) <= $days
            && $this->expiration_date->isFuture();
    }

    public function isExpired(): bool
    {
        if (!$this->expiration_date) {
            return false;
        }
        
        return $this->expiration_date->isPast();
    }

    // Seafood-specific methods

    /**
     * Check if this is a seafood product
     */
    public function isSeafood(): bool
    {
        return !is_null($this->fish_type) || !is_null($this->catch_date);
    }

    /**
     * Scope to filter by fish type
     */
    public function scopeByFishType($query, string $fishType)
    {
        return $query->where('fish_type', $fishType);
    }

    /**
     * Scope to filter frozen products only
     */
    public function scopeFrozen($query)
    {
        return $query->where('is_frozen', true);
    }

    /**
     * Scope to filter by freshness grade
     */
    public function scopeByGrade($query, string $grade)
    {
        return $query->where('freshness_grade', $grade);
    }

    /**
     * Scope to filter by origin waters
     */
    public function scopeFromWaters($query, string $waters)
    {
        return $query->where('origin_waters', $waters);
    }

    /**
     * Get days until expiration
     */
    public function daysUntilExpiry(): ?int
    {
        if (!$this->expiration_date) {
            return null;
        }

        if ($this->expiration_date->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expiration_date, false);
    }

    /**
     * Get human-readable freshness status
     */
    public function getFreshnessStatus(): string
    {
        $days = $this->daysUntilExpiry();
        
        if ($days === null) {
            return 'N/A';
        }

        if ($days <= 0) {
            return 'Expired';
        }

        if ($days <= 3) {
            return 'Expiring Soon';
        }

        if ($days <= 7) {
            return 'Fresh';
        }

        return 'Very Fresh';
    }

    /**
     * Get freshness color for UI (green, yellow, red)
     */
    public function getFreshnessColor(): string
    {
        $days = $this->daysUntilExpiry();
        
        if ($days === null) {
            return 'grey';
        }

        if ($days <= 0) {
            return 'red';
        }

        if ($days <= 3) {
            return 'orange';
        }

        if ($days <= 7) {
            return 'yellow';
        }

        return 'green';
    }
}
