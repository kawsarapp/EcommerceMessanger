<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        // ─── Identity & Pricing
        'name',
        'description',
        'color',
        'badge_text',
        'sort_order',
        'is_featured',
        'is_active',
        'features',        // JSON array of custom bullets

        // ─── Pricing
        'price',
        'yearly_price',
        'duration_days',
        'trial_days',

        // ─── Core Limits
        'product_limit',
        'order_limit',
        'ai_message_limit',
        'whatsapp_limit',
        'storage_limit_mb',
        'staff_account_limit',

        // ─── Feature Toggles
        'allow_custom_domain',
        'remove_branding',
        'priority_support',
        'allow_api_access',
        'allow_whatsapp',
        'allow_telegram',
        'allow_coupon',
        'allow_review',
        'allow_abandoned_cart',
        'allow_marketing_broadcast',
        'allow_analytics',
        'allow_premium_themes',
        'allow_payment_gateway',
        'allow_delivery_integration',
        'allow_facebook_messenger',
        'allow_ai',
        'allowed_ai_models',
        'allow_own_api_key',
        'hidden_menus',
    ];

    protected $casts = [
        'features'                 => 'array',
        'is_featured'              => 'boolean',
        'is_active'                => 'boolean',
        'hidden_menus'             => 'array',
        'allow_custom_domain'      => 'boolean',
        'remove_branding'          => 'boolean',
        'priority_support'         => 'boolean',
        'allow_api_access'         => 'boolean',
        'allow_whatsapp'           => 'boolean',
        'allow_telegram'           => 'boolean',
        'allow_coupon'             => 'boolean',
        'allow_review'             => 'boolean',
        'allow_abandoned_cart'     => 'boolean',
        'allow_marketing_broadcast'=> 'boolean',
        'allow_analytics'           => 'boolean',
        'allow_premium_themes'      => 'boolean',
        'allow_payment_gateway'     => 'boolean',
        'allow_delivery_integration'=> 'boolean',
        'allow_facebook_messenger'  => 'boolean',
        'allow_ai'                  => 'boolean',
        'allowed_ai_models'         => 'array',
        'allow_own_api_key'         => 'boolean',
        'price'                     => 'decimal:2',
        'yearly_price'              => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    // ─── Helpers ────────────────────────────────────────────────

    /** বার্ষিক সঞ্চয় কত % */
    public function getYearlySavingsPercentAttribute(): int
    {
        if (!$this->yearly_price || !$this->price) return 0;
        $monthly12 = $this->price * 12;
        return (int) round((($monthly12 - $this->yearly_price) / $monthly12) * 100);
    }

    /** Unlimited দেখাবে 0 হলে */
    public function limitLabel(int $value, string $unit = ''): string
    {
        return $value === 0 ? 'Unlimited' : number_format($value) . ($unit ? " $unit" : '');
    }
}
