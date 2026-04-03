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
        // ─── Sales & Growth Features
        'allow_flash_sale',
        'allow_loyalty',
        'allow_referral',
        'allow_return_refund',
        'allow_webhook',
        'allow_api_rate_limit',

        // ─── New Premium & Channel Features
        'allow_instagram',
        'allow_sms_notification',
        'allow_popup_banner',
        'allow_multi_currency',
        'allow_product_video',
        'allow_bulk_import',
        'allow_custom_checkout',
        'allow_pos_mode',
        'allow_live_chat_support',
        'allow_email_marketing',
        'allow_advanced_seo',
        'allow_subscription_product',
        'allow_store_locator',
        'allow_product_comparison',

        // ─── Hidden Menus Control
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
        // Sales & Growth
        'allow_flash_sale'          => 'boolean',
        'allow_loyalty'             => 'boolean',
        'allow_referral'            => 'boolean',
        'allow_return_refund'       => 'boolean',
        'allow_webhook'             => 'boolean',
        'allow_api_rate_limit'      => 'boolean',
        // Premium Features
        'allow_instagram'           => 'boolean',
        'allow_sms_notification'    => 'boolean',
        'allow_popup_banner'        => 'boolean',
        'allow_multi_currency'      => 'boolean',
        'allow_product_video'       => 'boolean',
        'allow_bulk_import'         => 'boolean',
        'allow_custom_checkout'     => 'boolean',
        'allow_pos_mode'            => 'boolean',
        'allow_live_chat_support'   => 'boolean',
        'allow_email_marketing'     => 'boolean',
        'allow_advanced_seo'        => 'boolean',
        'allow_subscription_product'=> 'boolean',
        'allow_store_locator'       => 'boolean',
        'allow_product_comparison'  => 'boolean',
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
