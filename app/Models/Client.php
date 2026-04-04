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
        'popup_pages'                  => 'array',
        'seller_settings'              => 'array',
        'require_pre_chat_form'        => 'boolean',
        'tracking_settings'            => 'array',
        'payment_gateways'             => 'array',
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
     * Auto-generate api_token on create if not set.
     */
    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            if (empty($client->api_token)) {
                $client->api_token = \Illuminate\Support\Str::random(60);
            }
        });
    }

    /**
     * Widget toggle helper - সেলার কোন UI element ON/OFF রেখেছে চেক করবে
     * Default: সব কিছু ON থাকবে যতক্ষণ না সেলার নিজে বন্ধ করবে
     */
    public function widget(string $key, bool $default = true): bool
    {
        $widgets = $this->widgets ?? [];
        if (isset($widgets[$key]) && is_array($widgets[$key])) {
            return $widgets[$key]['active'] ?? $default;
        }
        return (bool) ($widgets[$key] ?? $default);
    }

    /**
     * Widget configuration helper - উইজেটের কালার, টেক্সট, লিংক ইত্যাদি আনবে
     */
    public function widgetConfig(string $key): array
    {
        $widgets = $this->widgets ?? [];
        return is_array($widgets[$key] ?? null) ? $widgets[$key] : [];
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

    public function planUpgradeRequests(): HasMany
    {
        return $this->hasMany(PlanUpgradeRequest::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }
    
    public function externalConnections(): HasMany
    {
        return $this->hasMany(ExternalStoreConnection::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }
    
    public function shippingMethods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }

    // ==========================================
    // HELPER METHODS (Logic & Limits)
    // ==========================================

    /**
     * চেক করবে ক্লায়েন্টের প্ল্যান একটিভ আছে কিনা
     *
     * ✅ Admin Bypass: admin_permissions-এ যদি কোনো feature override থাকে
     *    এবং status 'active' হয়, তাহলে plan না থাকলেও বা expire হলেও
     *    এটি true দেবে — seller কাজ করতে পারবে।
     */
    public function hasActivePlan(): bool
    {
        // ১. Super Admin হলে বাইপাস
        if ($this->user?->isSuperAdmin()) return true;

        // ২. স্ট্যাটাস inactive/suspended হলে সব বন্ধ
        if ($this->status === 'inactive') {
            return false;
        }

        // ৩. Admin Permission Override আছে কিনা চেক
        //    যদি super admin অন্তত একটা feature override করে রাখে,
        //    তাহলে plan expire/নেই হলেও seller operate করতে পারবে
        $adminPerms = $this->admin_permissions ?? [];
        if (!empty($adminPerms) && $this->status === 'active') {
            return true;
        }

        // ৪. Plan আইডি না থাকলে false
        if (!$this->plan_id) {
            return false;
        }

        // ৫. মেয়াদ শেষ হয়ে গেছে কিনা চেক
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
        $adminPerms = $this->admin_permissions ?? [];
        $limit = isset($adminPerms['product_limit'])
            ? (int) $adminPerms['product_limit']
            : (int) ($this->plan?->product_limit ?? -1);

        if ($limit === -1) return true;  // plan নেই, কোনো override নেই
        if ($limit === 0) return false;  // 0 = Unlimited

        return $this->products()->count() >= $limit;
    }

    /**
     * এই মাসের অর্ডার লিমিট ক্রস করেছে কিনা
     */
    public function hasReachedOrderLimit(): bool
    {
        $adminPerms = $this->admin_permissions ?? [];
        $limit = isset($adminPerms['order_limit'])
            ? (int) $adminPerms['order_limit']
            : (int) ($this->plan?->order_limit ?? -1);

        if ($limit === -1) return true;  // plan নেই, কোনো override নেই
        if ($limit === 0) return false;  // 0 = Unlimited

        $monthlyOrders = $this->orders()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return $monthlyOrders >= $limit;
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
     * ✅ যদি admin_permissions-এ override থাকে, তাহলে plan active আছে কিনা
     *    সেটা check করে না — seller-এর জন্যও কাজ করবে।
     * ✅ Override না থাকলে plan-এর feature flag থেকে নেয়।
     */
    public function canAccessFeature(string $feature): bool
    {
        // Super Admin client always has all features
        if ($this->user?->isSuperAdmin()) return true;

        // 🔑 Check admin_permissions override FIRST (plan active না থাকলেও কাজ করবে)
        $adminPerms = $this->admin_permissions ?? [];
        if (array_key_exists($feature, $adminPerms)) {
            return (bool) $adminPerms[$feature];
        }

        // Admin override নেই — এখন plan active আছে কিনা check করো
        if (!$this->hasActivePlan()) {
            return false;
        }

        // Fall back to plan feature flag
        if ($this->plan) {
            return (bool) ($this->plan->$feature ?? false);
        }

        return false;
    }

    /**
     * ✅ Seller/Staff এর canViewAny() এ ব্যবহারের জন্য shortcut:
     *    admin_permissions override আছে কিনা চেক করে।
     *    থাকলে plan-active requirement bypass হবে।
     */
    public function hasAdminOverrideFor(string $feature): bool
    {
        $adminPerms = $this->admin_permissions ?? [];
        return array_key_exists($feature, $adminPerms);
    }

    /**
     * Seller নিজে feature enable রেখেছে কিনা check করো
     * Default: true (যদি seller explicitly off না করে)
     */
    public function sellerEnabled(string $feature): bool
    {
        $settings = $this->seller_settings ?? [];
        return (bool) ($settings["{$feature}_enabled"] ?? true);
    }

    // ==========================================
    // PAYMENT GATEWAY HELPERS
    // ==========================================

    /**
     * কোনো payment gateway active আছে কিনা চেক করো
     * Usage: $client->isPaymentGatewayActive('bkash_merchant')
     */
    public function isPaymentGatewayActive(string $gateway): bool
    {
        $gateways = $this->payment_gateways ?? [];
        return (bool) ($gateways[$gateway]['active'] ?? false);
    }

    /**
     * Payment gateway এর config আনো
     * Usage: $client->getPaymentGatewayConfig('sslcommerz')
     */
    public function getPaymentGatewayConfig(string $gateway): array
    {
        $gateways = $this->payment_gateways ?? [];
        return $gateways[$gateway] ?? [];
    }

    /**
     * Shop এ কোন কোন active payment methods আছে — checkout এ use হবে
     */
    public function getActivePaymentMethods(): array
    {
        $methods  = [];
        $gateways = $this->payment_gateways ?? [];

        // COD — default on unless explicitly disabled
        if ($this->cod_active ?? true) {
            $methods["cod"] = "Cash on Delivery";
        }

        // Partial / Full pre-payment
        if ($this->partial_payment_active ?? false) {
            $methods["partial"] = "Partial Advance Payment";
        }
        if ($this->full_payment_active ?? false) {
            $methods["full"] = "Full Pre-Payment";
        }

        // bKash PGW (Official Tokenized Checkout API)
        if (!empty($gateways["bkash_pgw"]["active"]) && !empty($gateways["bkash_pgw"]["app_key"])) {
            $methods["bkash_pgw"] = "bKash (Official Gateway)";
        }

        // bKash Merchant
        if (!empty($gateways["bkash_merchant"]["active"]) && !empty($gateways["bkash_merchant"]["number"])) {
            $methods["bkash_merchant"] = "bKash Merchant";
        }

        // bKash Personal
        if (!empty($gateways["bkash_personal"]["active"]) && !empty($gateways["bkash_personal"]["number"])) {
            $methods["bkash_personal"] = "bKash Personal";
        }

        // SSL Commerz — needs store_id
        if (!empty($gateways["sslcommerz"]["active"]) && !empty($gateways["sslcommerz"]["store_id"])) {
            $methods["sslcommerz"] = "Online Payment (SSL Commerz)";
        }

        // Surjopay — needs username (NOT merchant_id)
        if (!empty($gateways["surjopay"]["active"]) && !empty($gateways["surjopay"]["username"])) {
            $methods["surjopay"] = "Surjopay";
        }

        // Safety: always have at least COD
        if (empty($methods)) {
            $methods["cod"] = "Cash on Delivery";
        }

        return $methods;
    }

    /**
     * Get effective limit — admin override trumps plan limit
     * Returns 0 if not overridden and no plan (use plan default)
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

    /**
     * প্ল্যানের মেয়াদ শেষ হয়েছে কিনা (UI ব্যানারের জন্য)
     */
    public function isPlanExpired(): bool
    {
        if ($this->user?->isSuperAdmin()) return false;
        if ($this->status === 'inactive') return true;
        if (!$this->plan_id) return true;
        return $this->plan_ends_at && now()->gt($this->plan_ends_at);
    }

    /**
     * Plan expires কত দিন পরে
     */
    public function daysUntilExpiry(): int
    {
        if (!$this->plan_ends_at) return 0;
        return max(0, (int) now()->diffInDays($this->plan_ends_at, false));
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