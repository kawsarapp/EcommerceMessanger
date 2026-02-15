<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
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
        'order_status', // processing, shipped, etc.

        // Payment Info
        'payment_status',
        'payment_method',
        'transaction_id',

        // Notes
        'customer_note',
        'admin_note', // 🔥 AI Note (Size/Color info here)
        'notes',      // Backup
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

    // Alias for easier access
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
