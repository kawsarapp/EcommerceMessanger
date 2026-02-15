<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price', // প্রতি পিসের দাম
        'price',      // মোট দাম (quantity * unit_price)
        'attributes', // 🔥 New: যদি ভবিষ্যতে সাইজ/কালার স্পেসিফিক ডাটা সেভ করতে চান
    ];

    // ✅ সঠিক ডাটা টাইপ নিশ্চিত করার জন্য Casts
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'price' => 'decimal:2',
        'attributes' => 'array',
    ];

    // 🔥 অর্ডার আইটেম আপডেট হলে মেইন অর্ডারের টাইমস্ট্যাম্পও আপডেট হবে
    protected $touches = ['order'];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}