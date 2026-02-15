<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'client_id',
        'category_id', // ✅ Correct Foreign Key
        'name',
        'slug',
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

    // ✅ Casts for Arrays & Numbers
    protected $casts = [
        'key_features' => 'array',
        'gallery' => 'array',
        'colors' => 'array',
        'sizes' => 'array',
        'is_featured' => 'boolean',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    protected $attributes = [
        'regular_price' => 0,
        'stock_status' => 'in_stock',
    ];

    // 🔥 Auto-generate Slug (অটোমেটিক লিংক তৈরি করবে)
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->slug)) {
                // নাম থেকে স্লাগ + ইউনিক কোড (যাতে ডুপ্লিকেট না হয়)
                $product->slug = Str::slug($product->name) . '-' . Str::random(6);
            }
        });
    }

    // ===========================
    // 🔗 Relationships
    // ===========================

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // 🔥 New: ট্র্যাকিং এবং রিপোর্টের জন্য এটি লাগবে
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ===========================
    // 🛠 Accessors
    // ===========================

    public function getImageUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : asset('images/default-product.png');
    }
}