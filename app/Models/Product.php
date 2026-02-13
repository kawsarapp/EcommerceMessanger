<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'client_id',
        'category_id', // ✅ Correct Foreign Key
        'name',
        'slug',
        // 'category', // ❌ REMOVED: This column does not exist in your DB table
        'sub_category',
        'brand',
        'tags',
        'regular_price',
        'sale_price',
        'discount_type',
        'tax',
        'currency',
        'description',
        'short_description',
        'long_description',
        'key_features',
        'thumbnail',
        'gallery',
        'video_url',
        'sku',
        'stock_quantity',
        'stock_status',
        'colors',
        'sizes',
        'material',
        'avg_rating',
        'total_reviews',
        'weight',
        'dimensions',
        'shipping_class',
        'meta_title',
        'meta_description',
        'warranty',
        'return_policy',
        'is_featured',
    ];

    protected $casts = [
        'key_features' => 'array',
        'gallery' => 'array',
        'colors' => 'array',
        'sizes' => 'array',
        'is_featured' => 'boolean',
    ];

    protected $attributes = [
        'regular_price' => 0,
        'stock_status' => 'in_stock',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : asset('images/default-product.png');
    }

    // ✅ Relationship to Category (Only one definition allowed)
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}