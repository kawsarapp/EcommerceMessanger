<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'client_id',
        'product_id',
        'color',
        'size',
        'sku',
        'price',
        'stock_quantity',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'stock_quantity' => 'integer',
    ];

    protected $touches = ['product'];

    protected static function booted()
    {
        static::saved(function ($variant) {
            $variant->product?->recalculateStock();
        });

        static::deleted(function ($variant) {
            $variant->product?->recalculateStock();
        });
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
