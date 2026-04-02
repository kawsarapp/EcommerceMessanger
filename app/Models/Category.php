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

    protected $casts = [
        'is_visible' => 'boolean',
        'is_global'  => 'boolean',
    ];

    // ── Auto-slug ───────────────────────────────────────
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // ── Relationships ────────────────────────────────────

    /** এই ক্যাটাগরির অধীনে থাকা সব প্রোডাক্ট */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** শুধুমাত্র স্টকে থাকা এবং একটিভ প্রোডাক্টগুলো */
    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)->where('stock_status', 'in_stock');
    }

    /** SaaS: এই ক্যাটাগরিটি কোন ক্লায়েন্টের */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Parent category */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** Sub-categories */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // ── Scope Helpers ────────────────────────────────────

    /**
     * একটি নির্দিষ্ট seller-এর জন্য যে categories দেখানো উচিত:
     *   - global (super admin তৈরি) categories
     *   - seller-এর নিজের private categories
     */
    public static function forClient(int $clientId)
    {
        return static::where(function ($q) use ($clientId) {
            $q->where('is_global', true)
              ->orWhere('client_id', $clientId);
        });
    }
}