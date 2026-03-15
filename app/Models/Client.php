<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    // 🔥 FIX: বিশাল fillable লিস্টের বদলে শুধু guarded ব্যবহার করা হলো। 
    // এখন থেকে custom_domain সহ যেকোনো নতুন কলাম ডাটাবেসে সহজেই সেভ হবে।
    protected $guarded = ['id'];

    /**
     * ডাটা টাইপ কাস্টিং
     */
    protected $casts = [
        'plan_ends_at' => 'datetime',
        'webhook_verified_at' => 'datetime',
        'is_ai_enabled' => 'boolean',
        'is_review_collection_active' => 'boolean',
        'last_inventory_sync_at' => 'datetime',
        'is_reminder_active' => 'boolean',
        'is_whatsapp_active' => 'boolean',
        'widgets' => 'array',
        'ai_model' => 'string',
        'gemini_api_key' => 'string',
        'openai_api_key' => 'string',
        'deepseek_api_key' => 'string',
        'claude_api_key' => 'string',
        'groq_api_key' => 'string',
        // Inbox & Notifications 
        'is_notification_active' => 'boolean',
        'notify_emails' => 'array',
        'notify_telegram' => 'string',
        // Shop Display Settings
        'show_stock' => 'boolean',
        'show_related_products' => 'boolean',
        'show_return_warranty' => 'boolean',
        'cod_active' => 'boolean',
        'partial_payment_active' => 'boolean',
        'full_payment_active' => 'boolean',
        'footer_links' => 'array',
        'popup_active' => 'boolean',
        'popup_expires_at' => 'datetime',
        'homepage_banner_active' => 'boolean',
        'homepage_banner_timer' => 'datetime',
        'admin_permissions' => 'array',
        'popup_pages' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'gemini_api_key',
        'openai_api_key',
        'deepseek_api_key',
        'claude_api_key',
        'groq_api_key',
    ];

    /**
     * Widget toggle helper - সেলার কোন UI element ON/OFF রেখেছে চেক করবে
     * Default: সব কিছু ON থাকবে যতক্ষণ না সেলার নিজে বন্ধ করবে
     */
    public function widget(string $key, bool $default = true): bool
    {
        $widgets = $this->widgets ?? [];
        return $widgets[$key] ?? $default;
    }

    // ==========================================
    // RELATIONSHIPS
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
        // ১. Super Admin হলে বাইপাস
        if ($this->user?->isSuperAdmin()) return true;

        // ২. প্ল্যান আইডি না থাকলে বা স্ট্যাটাস ইনঅ্যাক্টিভ হলে ফলস
        if (!$this->plan_id || $this->status !== 'active') {
            return false;
        }

        // ৩. মেয়াদ শেষ হয়ে গেছে কিনা চেক
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
        if (!$this->plan) return true; 
        if ($this->plan->product_limit == 0) return false; // 0 means Unlimited

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

    /**
     * [FIXED] এই মাসের এআই মেসেজ লিমিট ক্রস করেছে কিনা
     */
    public function hasReachedAiLimit(): bool
    {
        if (!$this->plan) return true;
        if ($this->plan->ai_message_limit == 0) return false;

        $monthlyMessages = $this->conversations()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        return $monthlyMessages >= $this->plan->ai_message_limit;
    }

    /**
     * 🔑 Central Permission Check: Admin Override > Plan Feature
     * 
     * Usage: $client->canAccessFeature('allow_ai')
     * 
     * First checks admin_permissions overrides (set by Super Admin),
     * then falls back to the Plan's feature flags.
     */
    public function canAccessFeature(string $feature): bool
    {
        // Super Admin client always has all features
        if ($this->user?->isSuperAdmin()) return true;

        // Check admin_permissions override first
        $adminPerms = $this->admin_permissions ?? [];
        if (array_key_exists($feature, $adminPerms)) {
            return (bool) $adminPerms[$feature];
        }

        // Fall back to plan
        if ($this->plan) {
            return (bool) ($this->plan->$feature ?? false);
        }

        return false;
    }

    /**
     * Get effective limit — admin override trumps plan limit
     * Returns -1 if not overridden (use plan), 0 = unlimited
     */
    public function getEffectiveLimit(string $limitKey): int
    {
        $adminPerms = $this->admin_permissions ?? [];
        if (isset($adminPerms[$limitKey]) && $adminPerms[$limitKey] >= 0) {
            return (int) $adminPerms[$limitKey];
        }
        return (int) ($this->plan?->$limitKey ?? 0);
    }

    // ==========================================
    // USAGE PERCENTAGE CALCULATIONS (Dashboard Widget)
    // ==========================================

    public function getProductUsagePercentage(): int
    {
        if (!$this->plan || $this->plan->product_limit == 0) return 0;
        
        $count = $this->products()->count();
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

    // ==========================================
    // ALTERNATIVE CHECKS
    // ==========================================

    public function hasActiveSubscription(): bool
    {
        if ($this->user?->isSuperAdmin()) return true; // Super Admin always active
        return $this->plan_ends_at && $this->plan_ends_at->isFuture();
    }


    protected static function boot()
    {
        parent::boot();

        // Notun client toiri howar shomoy automatic API Token generate korbe
        static::creating(function ($client) {
            if (empty($client->api_token)) {
                $client->api_token = \Illuminate\Support\Str::random(40);
            }
        });
    }

}