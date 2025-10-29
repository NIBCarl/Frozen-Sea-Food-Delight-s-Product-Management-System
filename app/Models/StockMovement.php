<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'type' => 'string',
        'quantity' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
