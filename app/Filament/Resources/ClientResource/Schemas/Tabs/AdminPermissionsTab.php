<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\KeyValue;

class AdminPermissionsTab
{
    public static function schema(): array
    {
        return [
            Section::make('🔑 Admin Permission Overrides')
                ->description('এই সেলারের জন্য পার-মিশন ওভাররাইড করুন। এখানে দেওয়া সেটিং তার প্ল্যান লিমিট ওভাররাইড করবে।')
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->schema([
                    Placeholder::make('plan_info')
                        ->label('Current Plan')
                        ->content(fn ($record) => $record?->plan?->name
                            ? "📦 {$record->plan->name} (৳{$record->plan->price}/mo)"
                            : 'No Plan Assigned')
                        ->columnSpanFull(),

                    Section::make('🤖 AI Access Override')
                        ->schema([
                            Grid::make()->columns(3)->schema([
                                Toggle::make('admin_permissions.allow_ai')
                                    ->label('Allow AI Bot')
                                    ->onColor('success')
                                    ->helperText('প্ল্যান না থাকলেও AI এক্সেস দেবে'),

                                Toggle::make('admin_permissions.allow_own_api_key')
                                    ->label('Allow Own API Key')
                                    ->onColor('info')
                                    ->helperText('নিজের Key দিয়ে AI চালাতে পারবে'),
                            ]),
                        ])->columns(1),

                    Section::make('💼 Feature Override')
                        ->schema([
                            Grid::make()->columns(3)->schema([
                                Toggle::make('admin_permissions.allow_coupon')
                                    ->label('Coupon System')
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.allow_review')
                                    ->label('Review System')
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.allow_custom_domain')
                                    ->label('Custom Domain')
                                    ->onColor('info'),

                                Toggle::make('admin_permissions.allow_analytics')
                                    ->label('Analytics')
                                    ->onColor('info'),

                                Toggle::make('admin_permissions.allow_marketing_broadcast')
                                    ->label('Broadcast'  )
                                    ->onColor('warning'),

                                Toggle::make('admin_permissions.allow_abandoned_cart')
                                    ->label('Abandoned Cart')
                                    ->onColor('warning'),

                                Toggle::make('admin_permissions.allow_whatsapp')
                                    ->label('WhatsApp Bot')
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.allow_telegram')
                                    ->label('Telegram Bot')
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.remove_branding')
                                    ->label('Remove Branding')
                                    ->onColor('danger'),

                                Toggle::make('admin_permissions.priority_support')
                                    ->label('Priority Support')
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.allow_premium_themes')
                                    ->label('Premium Themes')
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.allow_payment_gateway')
                                    ->label('Payment Gateway')
                                    ->onColor('success'),
                            ]),
                        ])->columns(1),

                    Section::make('📊 Limit Override')
                        ->description('0 = Unlimited. -1 = প্ল্যান থেকে নেবে (ডিফল্ট)।')
                        ->schema([
                            Grid::make()->columns(3)->schema([
                                TextInput::make('admin_permissions.product_limit')
                                    ->label('Max Products')
                                    ->numeric()
                                    ->placeholder('-1 (plan default)')
                                    ->helperText('0 = Unlimited'),

                                TextInput::make('admin_permissions.order_limit')
                                    ->label('Monthly Orders')
                                    ->numeric()
                                    ->placeholder('-1 (plan default)')
                                    ->helperText('0 = Unlimited'),

                                TextInput::make('admin_permissions.ai_message_limit')
                                    ->label('AI Replies/mo')
                                    ->numeric()
                                    ->placeholder('-1 (plan default)')
                                    ->helperText('0 = Unlimited'),

                                TextInput::make('admin_permissions.storage_limit_mb')
                                    ->label('Storage (MB)')
                                    ->numeric()
                                    ->placeholder('-1 (plan default)'),
                            ]),
                        ])->columns(1),

                    Section::make('📅 Plan Extension')
                        ->schema([
                            Grid::make()->columns(2)->schema([
                                Select::make('status')
                                    ->label('Shop Status')
                                    ->options([
                                        'active'    => '✅ Active',
                                        'inactive'  => '❌ Suspended',
                                        'trial'     => '🆓 Trial',
                                    ]),

                                DateTimePicker::make('plan_ends_at')
                                    ->label('Plan Expires On')
                                    ->helperText('ম্যানুয়ালি মেয়াদ বাড়ানো বা কমানো যাবে'),
                            ]),
                        ])->columns(1),
                ]),
        ];
    }
}
