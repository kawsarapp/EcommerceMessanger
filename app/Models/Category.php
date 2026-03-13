<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'client_id',
        'name', 
        'slug', 
        'description', 
        'image',
        'status',
        'banner_image', 'banner_link', 'sort_order', 'is_visible'
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    // 🔥 অটোমেটিক স্লাগ জেনারেশন (নাম লিখলে অটোমেটিক URL তৈরি হবে)
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * এই ক্যাটাগরির অধীনে থাকা সব প্রোডাক্ট
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * শুধুমাত্র স্টকে থাকা এবং একটিভ প্রোডাক্টগুলো পাওয়ার জন্য
     */
    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)->where('stock_status', 'in_stock');
    }

    /**
     * SaaS এর জন্য: এই ক্যাটাগরিটি কোন ক্লায়েন্ট/দোকানের
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}