<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_name', 
        'slug', 
        'status', // active / inactive
        'plan_id',
        'plan_ends_at',
        'delivery_charge_inside',
        'delivery_charge_outside',
        'fb_page_id', 
        'fb_page_token', 
        'fb_verify_token',
        'custom_prompt', // এটিও ফর্মে ছিল, তাই অ্যাড করা হলো
    ];

    /**
     * ডাটা টাইপ কাস্টিং (ডেট ক্যালকুলেশনের জন্য জরুরি)
     */
    protected $casts = [
        'plan_ends_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS (এগুলো মিসিং ছিল)
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    // ==========================================
    // HELPER METHODS (Logic & Limits)
    // ==========================================

    /**
     * চেক করবে ক্লায়েন্টের প্ল্যান একটিভ আছে কিনা
     */
    public function hasActivePlan(): bool
    {
        // ১. অ্যাডমিন (User ID 1) হলে বাইপাস
        if ($this->user_id === 1) return true;

        // ২. প্ল্যান আইডি না থাকলে বা স্ট্যাটাস ইনঅ্যাক্টিভ হলে ফলস
        if (!$this->plan_id || $this->status !== 'active') {
            return false;
        }

        // ৩. মেয়াদ শেষ হয়ে গেছে কিনা চেক (যদি null হয়, তার মানে আনলিমিটেড টাইম)
        if ($this->plan_ends_at && now()->gt($this->plan_ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * প্রোডাক্ট লিমিট ক্রস করেছে কিনা
     */
    public function hasReachedProductLimit(): bool
    {
        if (!$this->plan) return true; // প্ল্যান না থাকলে লিমিট রিচড (ব্লক)
        
        // যদি লিমিট ০ হয় তার মানে আনলিমিটেড (অপশনাল), অথবা কাউন্ট চেক
        if ($this->plan->product_limit == 0) return false;

        return $this->products()->count() >= $this->plan->product_limit;
    }

    /**
     * এই মাসের অর্ডার লিমিট ক্রস করেছে কিনা
     */
    public function hasReachedOrderLimit(): bool
    {
        if (!$this->plan) return true;
        
        if ($this->plan->order_limit == 0) return false;

        $monthlyOrders = $this->orders()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        return $monthlyOrders >= $this->plan->order_limit;
    }

    // ==========================================
    // USAGE PERCENTAGE CALCULATIONS (Dashboard Widget)
    // ==========================================

    public function getProductUsagePercentage(): int
    {
        if (!$this->plan || $this->plan->product_limit == 0) return 0;
        
        $count = $this->products()->count();
        // ১০০ এর বেশি যেন না দেখায়
        return min(100, round(($count / $this->plan->product_limit) * 100));
    }

    public function getOrderUsagePercentage(): int
    {
        if (!$this->plan || $this->plan->order_limit == 0) return 0;
        
        $count = $this->orders()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        return min(100, round(($count / $this->plan->order_limit) * 100));
    }

    public function getAiUsagePercentage(): int
    {
        if (!$this->plan || $this->plan->ai_message_limit == 0) return 0;
        
        $count = $this->conversations()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        return min(100, round(($count / $this->plan->ai_message_limit) * 100));
    }
}