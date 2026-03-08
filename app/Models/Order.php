<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    // 🔥 AUTO WHATSAPP NOTIFICATION LOGIC
    // ==========================================
    protected static function booted()
    {
        static::updated(function ($order) {
            // যদি শুধুমাত্র order_status পরিবর্তন হয়
            if ($order->isDirty('order_status')) {
                $client = $order->client;

                // ক্লায়েন্টের হোয়াটসঅ্যাপ কানেক্টেড এবং Auto Status SMS অন থাকলে
                if ($client && $client->is_whatsapp_active && $client->auto_status_update_msg && $client->wa_instance_id) {
                    
                    $to = $order->customer_phone;
                    
                    // যদি কাস্টমার ফোন নাম্বার না থাকে, তবে sender_id (WhatsApp ID) ব্যবহার করবে
                    if (empty($to) && $order->sender_id) {
                        $to = $order->sender_id;
                    }

                    if (!empty($to)) {
                        // বাংলাদেশি নাম্বারের আগে 88 বসানোর লজিক
                        $cleanNumber = preg_replace('/[^0-9]/', '', $to);
                        if (strlen($cleanNumber) == 11 && str_starts_with($cleanNumber, '01')) {
                            $cleanNumber = '88' . $cleanNumber;
                        } elseif (strlen($cleanNumber) > 11 && !str_starts_with($cleanNumber, '88') && str_starts_with($cleanNumber, '01')) {
                             // অন্যান্য ক্ষেত্রের জন্য
                             $cleanNumber = $to; 
                        }

                        $shopName = $client->shop_name;
                        $status = strtoupper($order->order_status);
                        $customerName = $order->customer_name ?? 'Sir/Madam';

                        // সুন্দর একটি বাংলা+ইংরেজি মিক্স মেসেজ তৈরি
                        $message = "হ্যালো {$customerName},\n\nআপনার অর্ডারটির (ID: #{$order->id}) বর্তমান স্ট্যাটাস আপডেট হয়ে *{$status}* হয়েছে।\n\nআমাদের সাথে থাকার জন্য ধন্যবাদ! 🛍️\n- {$shopName}";

                        // Node.js সার্ভারে মেসেজ পাঠানোর রিকোয়েস্ট
                        try {
                            Http::post('http://127.0.0.1:3001/api/send-message', [
                                'instance_id' => $client->wa_instance_id,
                                'to' => $cleanNumber,
                                'message' => $message
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Auto Order Status WA Error: ' . $e->getMessage());
                        }
                    }
                }
            }
        });
    }

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

    // ট্র্যাকিং পেজ এবং টেলিগ্রাম রিপোর্টের জন্য সরাসরি প্রোডাক্ট এক্সেস
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            OrderItem::class,
            'order_id', 
            'id', 
            'id', 
            'product_id' 
        );
    }
}