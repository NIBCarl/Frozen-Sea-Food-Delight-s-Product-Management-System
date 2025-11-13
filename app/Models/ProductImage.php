<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['path', 'thumbnail_url'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessor that returns the full public URL for the stored image
    public function getPathAttribute()
    {
        return Storage::url($this->image_path);
    }

    // Alias for components that expect a 'thumbnail_url' attribute
    public function getThumbnailUrlAttribute()
    {
        return Storage::url($this->image_path);
    }
}
