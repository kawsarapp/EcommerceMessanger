<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use App\Models\Client;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;

/**
 * FeaturesTab — Seller নিজে features on/off করতে পারবে।
 * Plan এ feature না থাকলে toggle disabled।
 * Admin override থাকলে enabled।
 */
class FeaturesTab
{
    public static function schema(): array
    {
        $isAdmin = auth()->user()?->isSuperAdmin() ?? false;

        // Helper: plan/admin permission check করার closure
        $canUse = fn(string $feature) => function ($record) use ($feature) {
            if (!$record) return false;
            return $record->canAccessFeature($feature);
        };

        $disabledHint = fn(string $feature, string $label) => fn($record) =>
            !$record?->canAccessFeature($feature)
                ? "আপনার plan এ {$label} নেই — Upgrade করুন বা Admin এর সাথে যোগাযোগ করুন"
                : null;

        return [
            Section::make('⚙️ Feature Settings')
                ->description('আপনার shop এর জন্য কোন features চালু রাখতে চান নিজেই manage করুন।')
                ->schema([

                    // ─── 🛒 SALES FEATURES ──────────────────────────────
                    Section::make('🛒 Sales Features')->schema([
                        Toggle::make('seller_settings.coupon_enabled')
                            ->label('✅ Coupon / Promo Code')
                            ->helperText('কাস্টমার order এ coupon code apply করতে পারবে')
                            ->onColor('success')->offColor('danger')
                            ->default(true)
                            ->disabled(fn($record) => !$record?->canAccessFeature('allow_coupon'))
                            ->hint(fn($record) => !$record?->canAccessFeature('allow_coupon') ? '🔒 Plan এ নেই' : null)
                            ->hintColor('danger'),

                        Toggle::make('seller_settings.flash_sale_enabled')
                            ->label('⚡ Flash Sale / Countdown')
                            ->helperText('নির্দিষ্ট সময়ের জন্য discount campaign — Website এ countdown দেখাবে')
                            ->onColor('danger')->offColor('gray')
                            ->default(false)
                            ->disabled(fn($record) => !$record?->canAccessFeature('allow_flash_sale'))
                            ->hint(fn($record) => !$record?->canAccessFeature('allow_flash_sale') ? '🔒 Plan এ নেই' : null)
                            ->hintColor('danger'),

                        Toggle::make('seller_settings.referral_enabled')
                            ->label('🎁 Referral Program')
                            ->helperText('কাস্টমার বন্ধুকে refer করলে reward পাবে')
                            ->onColor('success')->offColor('gray')
                            ->default(false)
                            ->disabled(fn($record) => !$record?->canAccessFeature('allow_referral'))
                            ->hint(fn($record) => !$record?->canAccessFeature('allow_referral') ? '🔒 Plan এ নেই' : null)
                            ->hintColor('danger'),

                        Toggle::make('seller_settings.loyalty_enabled')
                            ->label('⭐ Loyalty Points')
                            ->helperText('প্রতি order এ customer points earn করবে')
                            ->onColor('warning')->offColor('gray')
                            ->default(false)
                            ->disabled(fn($record) => !$record?->canAccessFeature('allow_loyalty'))
                            ->hint(fn($record) => !$record?->canAccessFeature('allow_loyalty') ? '🔒 Plan এ নেই' : null)
                            ->hintColor('danger'),
                    ])->columns(2),

                    // ─── 📱 PLATFORM FEATURES ────────────────────────────
                    Section::make('📱 Platform Features')->schema([
                        Toggle::make('seller_settings.instagram_enabled')
                            ->label('📸 Instagram DM Bot')
                            ->helperText('Instagram Direct Message থেকে order নেওয়া')
                            ->onColor('success')->offColor('gray')
                            ->default(true),

                        Toggle::make('seller_settings.return_enabled')
                            ->label('📦 Return / Refund Flow')
                            ->helperText('কাস্টমার chatbot এ return request করতে পারবে')
                            ->onColor('success')->offColor('gray')
                            ->default(false)
                            ->disabled(fn($record) => !$record?->canAccessFeature('allow_return_refund'))
                            ->hint(fn($record) => !$record?->canAccessFeature('allow_return_refund') ? '🔒 Plan এ নেই' : null)
                            ->hintColor('danger'),

                        Toggle::make('seller_settings.webhook_enabled')
                            ->label('🔗 Webhook / Zapier')
                            ->helperText('Zapier/Make এর সাথে connect করুন')
                            ->onColor('info')->offColor('gray')
                            ->default(false)
                            ->disabled(fn($record) => !$record?->canAccessFeature('allow_webhook'))
                            ->hint(fn($record) => !$record?->canAccessFeature('allow_webhook') ? '🔒 Plan এ নেই' : null)
                            ->hintColor('danger'),
                    ])->columns(2),

                    // ─── 🌐 DOMAIN & API ──────────────────────────────────
                    Section::make('🌐 Domain & API')->schema([
                        TextInput::make('custom_domain')
                            ->label('Custom Domain')
                            ->placeholder('shop.yourdomain.com')
                            ->helperText('DNS CNAME → ' . (config('app.url') ?? 'asianhost.net') . ' করুন, তারপর এখানে লিখুন')
                            ->disabled(fn($record) => !$record?->canAccessFeature('allow_custom_domain'))
                            ->hint(fn($record) => !$record?->canAccessFeature('allow_custom_domain') ? '🔒 Plan এ নেই' : null)
                            ->hintColor('danger'),

                        TextInput::make('seller_settings.api_rate_limit_override')
                            ->label('API Rate Limit (requests/min)')
                            ->numeric()
                            ->placeholder('60')
                            ->helperText('Default plan limit override করতে পারবেন')
                            ->visible(fn() => $isAdmin),
                    ]),
                ]),

            // ─── ADMIN-ONLY PERMISSION OVERRIDES ──────────────────────────────────
            Section::make('🔑 Admin Permission Overrides')
                ->description('⚠️ Admin হিসেবে এই seller কে যে features দিতে চান সেগুলো override করুন। Plan limit supersede করবে।')
                ->visible(fn() => $isAdmin)
                ->collapsible()
                ->collapsed()
                ->schema([
                    Section::make('Feature Access Override')->schema([
                        Toggle::make('admin_permissions.allow_flash_sale')
                            ->label('⚡ Flash Sale')->onColor('danger')->offColor('gray'),
                        Toggle::make('admin_permissions.allow_referral')
                            ->label('🎁 Referral')->onColor('success')->offColor('gray'),
                        Toggle::make('admin_permissions.allow_loyalty')
                            ->label('⭐ Loyalty Points')->onColor('warning')->offColor('gray'),
                        Toggle::make('admin_permissions.allow_return_refund')
                            ->label('📦 Return/Refund')->onColor('warning')->offColor('gray'),
                        Toggle::make('admin_permissions.allow_webhook')
                            ->label('🔗 Webhooks')->onColor('info')->offColor('gray'),
                        Toggle::make('admin_permissions.allow_analytics')
                            ->label('📊 Analytics')->onColor('info')->offColor('gray'),
                        Toggle::make('admin_permissions.allow_custom_domain')
                            ->label('🌐 Custom Domain')->onColor('info')->offColor('gray'),
                        Toggle::make('admin_permissions.allow_coupon')
                            ->label('✅ Coupon System')->onColor('success')->offColor('gray'),
                    ])->columns(4),

                    Section::make('Limits Override')->schema([
                        TextInput::make('admin_permissions.product_limit')
                            ->label('Product Limit (0=∞)')->numeric()->placeholder('Plan default'),
                        TextInput::make('admin_permissions.order_limit')
                            ->label('Monthly Orders (0=∞)')->numeric()->placeholder('Plan default'),
                        TextInput::make('admin_permissions.ai_message_limit')
                            ->label('AI Messages (0=∞)')->numeric()->placeholder('Plan default'),
                        TextInput::make('admin_permissions.api_rate_limit')
                            ->label('API Rate Limit (req/min)')->numeric()->placeholder('60'),
                    ])->columns(4),
                ]),
        ];
    }
}
