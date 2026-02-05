<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
    'client_id', 
    'category_id', // নতুন যোগ করা
    'name', 
    'slug', 
    'category', 
    'sub_category', 
    'brand', 
    'tags',
    'regular_price', 
    'sale_price', 
    'discount_type', 
    'tax', 
    'currency',
    'description', // এটি Resource এর সাথে মিল রেখে আগেই ছিল
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
    'is_featured'
];

protected $casts = [
    'key_features' => 'array',
    'gallery' => 'array',
    'colors' => 'array',
    'sizes' => 'array',
    'is_featured' => 'boolean',
];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    
    // Accessor টি thumbnail অনুযায়ী ফিক্স করা হলো
    public function getImageUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : asset('images/default-product.png');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }


}