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
                                    ->default(fn ($record) => $record?->plan?->allow_ai ?? false)
                                    ->helperText('প্ল্যান না থাকলেও AI এক্সেস দেবে'),

                                Toggle::make('admin_permissions.allow_own_api_key')
                                    ->label('Allow Own API Key')
                                    ->onColor('info')
                                    ->default(fn ($record) => $record?->plan?->allow_own_api_key ?? false)
                                    ->helperText('নিজের Key দিয়ে AI চালাতে পারবে'),
                            ]),
                            
                            \Filament\Forms\Components\CheckboxList::make('admin_permissions.allowed_ai_models')
                                ->label('Allowed AI Models (খালি = सब মডেল)')
                                ->options([
                                    'gemini-pro'              => '🟦 Gemini 1.5 Flash',
                                    'gemini-pro-full'         => '🟦 Gemini 2.0 Flash',
                                    'gpt-4o'                  => '🟩 GPT-4o',
                                    'gpt-4o-mini'             => '🟩 GPT-4o Mini',
                                    'gpt-3.5-turbo'           => '🟩 GPT-3.5 Turbo',
                                    'claude-3-opus-20240229'  => '🟧 Claude 3 Opus',
                                    'claude-3-haiku-20240307' => '🟧 Claude 3 Haiku',
                                    'deepseek-chat'           => '🟪 DeepSeek Chat',
                                    'deepseek-reasoner'       => '🟪 DeepSeek R1',
                                ])
                                ->default(fn ($record) => $record?->plan?->allowed_ai_models ?? [])
                                ->columns(3)
                                ->helperText('কোন মডেলগুলো এই প্ল্যানে available থাকবে। খালি রাখলে সব মডেল পাবে।')
                                ->columnSpanFull(),
                        ])->columns(1),

                    Section::make('💼 Feature Override')
                        ->schema([
                            Grid::make()->columns(3)->schema([
                                Toggle::make('admin_permissions.allow_coupon')
                                    ->label('Coupon System')
                                    ->default(fn ($record) => $record?->plan?->allow_coupon ?? false)
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.allow_review')
                                    ->label('Review System')
                                    ->default(fn ($record) => $record?->plan?->allow_review ?? false)
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.allow_custom_domain')
                                    ->label('Custom Domain')
                                    ->default(fn ($record) => $record?->plan?->allow_custom_domain ?? false)
                                    ->onColor('info'),

                                Toggle::make('admin_permissions.allow_analytics')
                                    ->label('Analytics')
                                    ->default(fn ($record) => $record?->plan?->allow_analytics ?? false)
                                    ->onColor('info'),

                                Toggle::make('admin_permissions.allow_marketing_broadcast')
                                    ->label('Broadcast')
                                    ->default(fn ($record) => $record?->plan?->allow_marketing_broadcast ?? false)
                                    ->onColor('warning'),

                                Toggle::make('admin_permissions.allow_abandoned_cart')
                                    ->label('Abandoned Cart')
                                    ->default(fn ($record) => $record?->plan?->allow_abandoned_cart ?? false)
                                    ->onColor('warning'),

                                Toggle::make('admin_permissions.allow_whatsapp')
                                    ->label('WhatsApp Bot')
                                    ->default(fn ($record) => $record?->plan?->allow_whatsapp ?? false)
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.allow_telegram')
                                    ->label('Telegram Bot')
                                    ->default(fn ($record) => $record?->plan?->allow_telegram ?? false)
                                    ->onColor('success'),
                                    
                                Toggle::make('admin_permissions.allow_api_access')
                                    ->label('API Access')
                                    ->default(fn ($record) => $record?->plan?->allow_api_access ?? false)
                                    ->onColor('info'),
                                    
                                Toggle::make('admin_permissions.allow_delivery_integration')
                                    ->label('Delivery Integration')
                                    ->default(fn ($record) => $record?->plan?->allow_delivery_integration ?? false)
                                    ->onColor('success'),
                                    
                                Toggle::make('admin_permissions.allow_facebook_messenger')
                                    ->label('Facebook Messenger')
                                    ->default(fn ($record) => $record?->plan?->allow_facebook_messenger ?? false)
                                    ->onColor('info'),

                                Toggle::make('admin_permissions.remove_branding')
                                    ->label('Remove Branding')
                                    ->default(fn ($record) => $record?->plan?->remove_branding ?? false)
                                    ->onColor('danger'),

                                Toggle::make('admin_permissions.priority_support')
                                    ->label('Priority Support')
                                    ->default(fn ($record) => $record?->plan?->priority_support ?? false)
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.allow_premium_themes')
                                    ->label('Premium Themes')
                                    ->default(fn ($record) => $record?->plan?->allow_premium_themes ?? false)
                                    ->onColor('success'),

                                Toggle::make('admin_permissions.allow_payment_gateway')
                                    ->label('Payment Gateway')
                                    ->default(fn ($record) => $record?->plan?->allow_payment_gateway ?? false)
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
                                    
                                TextInput::make('admin_permissions.whatsapp_limit')
                                    ->label('WhatsApp Messages')
                                    ->numeric()
                                    ->placeholder('-1 (plan default)')
                                    ->helperText('0 = Unlimited'),
                                    
                                TextInput::make('admin_permissions.staff_account_limit')
                                    ->label('Staff Accounts')
                                    ->numeric()
                                    ->placeholder('-1 (plan default)')
                                    ->helperText('0 = Unlimited'),
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
