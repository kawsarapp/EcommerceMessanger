<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;

/**
 * FeaturesTab — Seller নিজে নিজে features on/off করতে পারবে।
 * Admin যদি allow না করেন, তাহলে toggle disabled থাকবে।
 */
class FeaturesTab
{
    public static function schema(): array
    {
        $isAdmin = auth()->user()?->isSuperAdmin() ?? false;

        return [
            // ─── SELLER SELF-SERVICE TOGGLES ──────────────────────────────────────
            Section::make('⚙️ Feature Settings')
                ->description('আপনার shop এর জন্য কোন features চালু রাখতে চান নিজেই manage করুন।')
                ->schema([
                    Section::make('🛒 Sales Features')->schema([
                        Toggle::make('seller_settings.coupon_enabled')
                            ->label('Coupon / Promo Code')
                            ->helperText('কাস্টমার order এ coupon code apply করতে পারবে')
                            ->onColor('success')->offColor('danger')
                            ->default(true),

                        Toggle::make('seller_settings.flash_sale_enabled')
                            ->label('Flash Sale')
                            ->helperText('নির্দিষ্ট সময়ের জন্য discount campaign চালু করুন')
                            ->onColor('success')->offColor('danger')
                            ->default(true),

                        Toggle::make('seller_settings.referral_enabled')
                            ->label('Referral Program')
                            ->helperText('কাস্টমার বন্ধুকে refer করলে reward পাবে')
                            ->onColor('success')->offColor('danger')
                            ->default(false),

                        Toggle::make('seller_settings.loyalty_enabled')
                            ->label('Loyalty Points')
                            ->helperText('প্রতি order এ customer points earn করবে')
                            ->onColor('success')->offColor('danger')
                            ->default(false),
                    ])->columns(2),

                    Section::make('📱 Platform Features')->schema([
                        Toggle::make('seller_settings.instagram_enabled')
                            ->label('Instagram DM Bot')
                            ->helperText('Instagram Direct Message থেকে order নেওয়া')
                            ->onColor('success')->offColor('danger')
                            ->default(true),

                        Toggle::make('seller_settings.return_enabled')
                            ->label('Return / Refund Flow')
                            ->helperText('কাস্টমার chatbot এ return request করতে পারবে')
                            ->onColor('success')->offColor('danger')
                            ->default(true),

                        Toggle::make('seller_settings.webhook_enabled')
                            ->label('Webhook / Zapier Integration')
                            ->helperText('Zapier/Make এর সাথে connect করুন')
                            ->onColor('success')->offColor('danger')
                            ->default(false),
                    ])->columns(2),

                    Section::make('🌐 Domain & API')->schema([
                        TextInput::make('custom_domain')
                            ->label('Custom Domain')
                            ->placeholder('shop.yourdomain.com')
                            ->helperText('DNS CNAME → ' . config('app.url') . ' করুন, তারপর এখানে লিখুন')
                            ->visible(fn () => true), // Allow_custom_domain plan check is in middleware

                        TextInput::make('seller_settings.api_rate_limit_override')
                            ->label('API Rate Limit (requests/min)')
                            ->numeric()
                            ->placeholder('60')
                            ->helperText('Default plan limit override করতে পারবেন')
                            ->visible(fn () => $isAdmin),
                    ]),
                ]),

            // ─── ADMIN-ONLY PERMISSION OVERRIDES ────────────────────────────────
            Section::make('🔑 Admin Permission Overrides')
                ->description('Admin হিসেবে এই seller কে যে features দিতে চান সেগুলো here override করুন। Plan limit supersede করবে।')
                ->visible(fn () => $isAdmin)
                ->schema([
                    Section::make('Feature Access')->schema([
                        Toggle::make('admin_permissions.allow_flash_sale')
                            ->label('Flash Sale')->onColor('success')->offColor('danger'),
                        Toggle::make('admin_permissions.allow_referral')
                            ->label('Referral')->onColor('success')->offColor('danger'),
                        Toggle::make('admin_permissions.allow_loyalty')
                            ->label('Loyalty Points')->onColor('success')->offColor('danger'),
                        Toggle::make('admin_permissions.allow_return_refund')
                            ->label('Return / Refund')->onColor('success')->offColor('danger'),
                        Toggle::make('admin_permissions.allow_webhook')
                            ->label('Webhooks / Zapier')->onColor('success')->offColor('danger'),
                        Toggle::make('admin_permissions.allow_analytics')
                            ->label('Analytics Dashboard')->onColor('success')->offColor('danger'),
                        Toggle::make('admin_permissions.allow_custom_domain')
                            ->label('Custom Domain')->onColor('success')->offColor('danger'),
                        Toggle::make('admin_permissions.allow_api_access')
                            ->label('API Access')->onColor('success')->offColor('danger'),
                    ])->columns(4),

                    Section::make('Limits Override')->schema([
                        TextInput::make('admin_permissions.product_limit')
                            ->label('Product Limit (0=unlimited)')
                            ->numeric()->placeholder('Plan default'),
                        TextInput::make('admin_permissions.order_limit')
                            ->label('Monthly Order Limit (0=unlimited)')
                            ->numeric()->placeholder('Plan default'),
                        TextInput::make('admin_permissions.ai_message_limit')
                            ->label('Monthly AI Message Limit (0=unlimited)')
                            ->numeric()->placeholder('Plan default'),
                        TextInput::make('admin_permissions.api_rate_limit')
                            ->label('API Rate Limit (req/min)')
                            ->numeric()->placeholder('60'),
                    ])->columns(4),
                ]),
        ];
    }
}
