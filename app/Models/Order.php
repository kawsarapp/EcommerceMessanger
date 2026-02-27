<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'client_id',
        'sender_id', // Messenger/Telegram User ID
        'customer_name',
        'customer_image',
        'customer_phone',
        'customer_email',

        // Address Info
        'division',
        'district',
        'shipping_address',

        // Order Info
        'total_amount',
        'order_status', // processing, shipped, delivered, cancelled
        
        // Payment Info
        'payment_status',
        'payment_method',
        'transaction_id',

        //---
        'courier_name',
        'tracking_code',
        //---

        // Notes
        'customer_note',
        'admin_note', // 🔥 AI Note (Size/Color info here)
        'notes',      // Backup Note
    ];

    // ✅ Casts for better data handling
    protected $casts = [
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Alias for easier access (Backward Compatibility)
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    // 🔥 NEW: ট্র্যাকিং পেজ এবং টেলিগ্রাম রিপোর্টের জন্য সরাসরি প্রোডাক্ট এক্সেস
    // এটি $order->products কল করলেই অর্ডারের সব প্রোডাক্ট এনে দিবে
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            OrderItem::class,
            'order_id', // Foreign key on order_items table...
            'id', // Foreign key on products table...
            'id', // Local key on orders table...
            'product_id' // Local key on order_items table...
        );
    }
}