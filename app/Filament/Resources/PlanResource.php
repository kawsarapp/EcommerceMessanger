<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'System Settings';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Subscription Plans';

    // 🔒 Super Admin Only
    public static function canViewAny(): bool  { return auth()->user()?->isSuperAdmin() ?? false; }
    public static function canCreate(): bool   { return auth()->user()?->isSuperAdmin() ?? false; }
    public static function canEdit(Model $record): bool   { return auth()->user()?->isSuperAdmin() ?? false; }
    public static function canDelete(Model $record): bool { return auth()->user()?->isSuperAdmin() ?? false; }

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ══════════════════════════════════════════
            // 🎨 SECTION 1: Identity & Branding
            // ══════════════════════════════════════════
            Section::make('🎨 Identity & Branding')
                ->description('প্ল্যানের নাম, দাম, রঙ এবং বিবরণ।')
                ->icon('heroicon-m-swatch')
                ->schema([
                    Grid::make()->columns(3)->schema([
                        TextInput::make('name')
                            ->label('Plan Name')
                            ->placeholder('e.g. Gold, Business Pro')
                            ->required()
                            ->maxLength(100)
                            ->prefixIcon('heroicon-m-tag')
                            ->columnSpan(2),

                        TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('কম নম্বর = আগে দেখাবে'),
                    ]),

                    Grid::make()->columns(['sm' => 3])->schema([
                        ColorPicker::make('color')
                            ->label('Brand Color')
                            ->default('#4f46e5'),

                        TextInput::make('badge_text')
                            ->label('Badge Label')
                            ->placeholder('e.g. Best Value, Hot Deal')
                            ->helperText('Pricing card-এ badge দেখাবে'),

                        Toggle::make('is_featured')
                            ->label('Mark as Featured (Recommended)')
                            ->onColor('warning')
                            ->helperText('Pricing পেজে highlighted থাকবে।'),
                    ]),

                    Textarea::make('description')
                        ->label('Short Description')
                        ->placeholder('Best for growing e-commerce businesses...')
                        ->rows(2)
                        ->columnSpanFull(),

                    TagsInput::make('features')
                        ->label('✨ Feature Bullets (Custom)')
                        ->placeholder('Enter a feature and press Enter')
                        ->helperText('এই bullets pricing পেজে দেখাবে। e.g. "24/7 Live Chat Support", "Free SSL Certificate"')
                        ->columnSpanFull(),
                ])->columns(1),

            // ══════════════════════════════════════════
            // 💰 SECTION 2: Pricing
            // ══════════════════════════════════════════
            Section::make('💰 Pricing & Duration')
                ->description('মাসিক ও বার্ষিক মূল্য এবং মেয়াদ।')
                ->icon('heroicon-m-banknotes')
                ->schema([
                    Grid::make()->columns(2)->schema([
                        TextInput::make('price')
                            ->label('Monthly Price (৳)')
                            ->numeric()
                            ->prefix('৳')
                            ->required()
                            ->placeholder('1499'),

                        TextInput::make('yearly_price')
                            ->label('Yearly Price (৳) — Optional')
                            ->numeric()
                            ->prefix('৳')
                            ->placeholder('14999')
                            ->helperText('বার্ষিক ডিসকাউন্ট দিলে এখানে দিন। খালি রাখলে বার্ষিক অপশন দেখাবে না।'),
                    ]),

                    Grid::make()->columns(2)->schema([
                        TextInput::make('duration_days')
                            ->label('Plan Duration (Days)')
                            ->numeric()
                            ->default(30)
                            ->suffix('দিন')
                            ->helperText('30 = মাসিক, 365 = বার্ষিক'),

                        TextInput::make('trial_days')
                            ->label('Free Trial Period (Days)')
                            ->numeric()
                            ->default(0)
                            ->suffix('দিন')
                            ->helperText('0 রাখলে কোনো ফ্রি ট্রায়াল নেই।'),
                    ]),
                ])->columns(1),

            // ══════════════════════════════════════════
            // 📊 SECTION 3: Core Usage Limits
            // ══════════════════════════════════════════
            Section::make('📊 Core Usage Limits')
                ->description('0 = Unlimited। প্রতিটি সীমা সাবধানে নির্ধারণ করুন।')
                ->icon('heroicon-m-adjustments-horizontal')
                ->schema([
                    Grid::make()->columns(3)->schema([
                        TextInput::make('product_limit')
                            ->label('Max Products')
                            ->numeric()
                            ->default(50)
                            ->suffix('টি')
                            ->helperText('0 = Unlimited')
                            ->required(),

                        TextInput::make('order_limit')
                            ->label('Monthly Orders')
                            ->numeric()
                            ->default(200)
                            ->suffix('টি/মাস')
                            ->helperText('0 = Unlimited')
                            ->required(),

                        TextInput::make('ai_message_limit')
                            ->label('AI Bot Replies')
                            ->numeric()
                            ->default(500)
                            ->suffix('টি/মাস')
                            ->helperText('0 = Unlimited')
                            ->required(),
                    ]),

                    Grid::make()->columns(3)->schema([
                        TextInput::make('whatsapp_limit')
                            ->label('WhatsApp Messages')
                            ->numeric()
                            ->default(0)
                            ->suffix('টি/মাস')
                            ->helperText('0 = Unlimited'),

                        TextInput::make('storage_limit_mb')
                            ->label('File Storage')
                            ->numeric()
                            ->default(500)
                            ->suffix('MB')
                            ->helperText('500 = 500MB'),

                        TextInput::make('staff_account_limit')
                            ->label('Staff Accounts')
                            ->numeric()
                            ->default(1)
                            ->suffix('জন')
                            ->helperText('কতজন sub-user যোগ করতে পারবে'),
                    ]),
                ]),

            // ══════════════════════════════════════════
            // 🤖 SECTION 4: Platform & Channel Access
            // ══════════════════════════════════════════
            Section::make('🤖 Platform & Channel Access')
                ->description('কোন কোন চ্যানেল ও প্ল্যাটফর্ম এই প্ল্যানে থাকবে।')
                ->icon('heroicon-m-device-phone-mobile')
                ->schema([
                    Grid::make()->columns(3)->schema([
                        Toggle::make('allow_telegram')
                            ->label('Telegram Bot')
                            ->onColor('success')
                            ->default(true)
                            ->helperText('Telegram bot ব্যবহার করতে পারবে'),

                        Toggle::make('allow_whatsapp')
                            ->label('WhatsApp Bot')
                            ->onColor('success')
                            ->helperText('WhatsApp bot চালু করতে পারবে'),

                        Toggle::make('allow_api_access')
                            ->label('API Access')
                            ->onColor('info')
                            ->helperText('Third-party API integration'),
                    ]),
                ])->columns(1),

            // ══════════════════════════════════════════
            // ⚡ SECTION 5: Advanced Features
            // ══════════════════════════════════════════
            Section::make('⚡ Advanced Features')
                ->description('উন্নত ফিচার যা শুধুমাত্র নির্দিষ্ট প্ল্যানে থাকবে।')
                ->icon('heroicon-m-sparkles')
                ->schema([
                    Grid::make()->columns(3)->schema([
                        Toggle::make('allow_coupon')
                            ->label('Coupon / Discount System')
                            ->onColor('success')
                            ->default(true)
                            ->helperText('Coupon code তৈরি করতে পারবে'),

                        Toggle::make('allow_review')
                            ->label('Customer Review System')
                            ->onColor('success')
                            ->default(true)
                            ->helperText('পণ্যে রিভিউ সংগ্রহ করতে পারবে'),

                        Toggle::make('allow_abandoned_cart')
                            ->label('Abandoned Cart Recovery')
                            ->onColor('warning')
                            ->helperText('অসম্পূর্ণ অর্ডারে AI reminder পাঠাবে'),
                    ]),

                    Grid::make()->columns(3)->schema([
                        Toggle::make('allow_marketing_broadcast')
                            ->label('Marketing Broadcast')
                            ->onColor('warning')
                            ->helperText('Bulk message পাঠাতে পারবে'),

                        Toggle::make('allow_analytics')
                            ->label('Advanced Analytics')
                            ->onColor('info')
                            ->helperText('Sales chart ও detailed report দেখতে পারবে'),

                        Toggle::make('allow_custom_domain')
                            ->label('Custom Domain Connection')
                            ->onColor('info')
                            ->helperText('নিজের domain (.com) সংযোগ করতে পারবে'),
                    ]),

                    Grid::make()->columns(3)->schema([
                        Toggle::make('remove_branding')
                            ->label('Remove NeuralCart Branding')
                            ->onColor('danger')
                            ->helperText('"Powered by NeuralCart" watermark সরাতে পারবে'),

                        Toggle::make('priority_support')
                            ->label('Priority Support')
                            ->onColor('success')
                            ->helperText('দ্রুত সাপোর্ট টিকেট সমাধান পাবে'),
                            
                        Toggle::make('allow_premium_themes')
                            ->label('Premium Themes')
                            ->onColor('success')
                            ->helperText('প্রিমিয়াম শপ থিম ব্যবহার করতে পারবে'),
                    ]),
                    
                    Grid::make()->columns(3)->schema([
                        Toggle::make('allow_payment_gateway')
                            ->label('Payment Gateways')
                            ->onColor('success')
                            ->helperText('bKash/SSLCommerz ইন্টিগ্রেশন'),

                        Toggle::make('allow_delivery_integration')
                            ->label('Delivery Integration')
                            ->onColor('success')
                            ->helperText('Steadfast/Pathao/RedX ইন্টিগ্রেশন'),
                            
                        Toggle::make('allow_facebook_messenger')
                            ->label('Facebook Integration')
                            ->onColor('info')
                            ->helperText('Facebook Messenger & Auto Reply'),
                    ]),
                ])->columns(1),

            // ══════════════════════════════════════════
            // 🤖 SECTION 5.5: AI Access Control
            // ══════════════════════════════════════════
            Section::make('🤖 AI Access Control')
                ->description('সেলাররা কোন AI ব্যবহার করতে পারবে এবং নিজের API Key দিতে পারবে কিনা।')
                ->icon('heroicon-m-cpu-chip')
                ->schema([
                    Grid::make()->columns(3)->schema([
                        Toggle::make('allow_ai')
                            ->label('AI Bot Access')
                            ->onColor('success')
                            ->default(true)
                            ->helperText('AI Bot চালু করতে পারবে কিনা'),

                        Toggle::make('allow_own_api_key')
                            ->label('Use Own API Key')
                            ->onColor('info')
                            ->default(false)
                            ->helperText('নিজের Gemini/OpenAI/DeepSeek key দিতে পারবে'),
                    ]),

                    \Filament\Forms\Components\CheckboxList::make('allowed_ai_models')
                        ->label('Allowed AI Models (খালি = সব মডেল)')
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
                        ->columns(3)
                        ->helperText('কোন মডেলগুলো এই প্ল্যানে available থাকবে। খালি রাখলে সব মডেল পাবে।')
                        ->columnSpanFull(),
                ])->columns(1),

            // ══════════════════════════════════════════
            // 🚫 SECTION 6: Hidden Menus Control
            // ══════════════════════════════════════════
            Section::make('🚫 Hidden Menus Control')
                ->description('এই প্ল্যানের আন্ডারে থাকা সকল সেলারদের জন্য কোন মেনুগুলো হাইড করা থাকবে তা নিরর্ধারণ করুন।')
                ->icon('heroicon-m-eye-slash')
                ->schema([
                    \Filament\Forms\Components\CheckboxList::make('hidden_menus')
                        ->label('Select the menus you want to hide for this plan:')
                        ->options([
                            'basic-info' => 'Basic Info',
                            'storefront' => 'Storefront',
                            'domain-seo' => 'Domain & SEO',
                            'ai-brain' => 'AI Brain & Automation',
                            'logistics' => 'Logistics',
                            'courier-api' => 'Courier API',
                            'integrations' => 'Integrations & Social',
                            'inbox-automation' => 'Inbox Automation',
                            'store-sync' => 'Store Sync',
                            'whatsapp-api' => 'WhatsApp API Settings',
                        ])
                        ->columns(3)
                        ->bulkToggleable(),
                ]),

            // ══════════════════════════════════════════
            // ✅ SECTION 7: Availability
            // ══════════════════════════════════════════
            Section::make('✅ Plan Availability')
                ->icon('heroicon-m-eye')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Active & Visible')
                        ->default(true)
                        ->onColor('success')
                        ->offColor('danger')
                        ->helperText('বন্ধ থাকলে Pricing পেজে দেখাবে না এবং নতুন কেউ subscribe করতে পারবে না।'),
                ])->collapsible(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->width(30),

                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                TextColumn::make('name')
                    ->label('Plan')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn (Plan $record) => $record->badge_text
                        ? "🏷 {$record->badge_text}"
                        : (\Illuminate\Support\Str::limit($record->description, 40) ?: null)
                    ),

                TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn (Plan $record) =>
                        '৳' . number_format($record->price) . '/mo' .
                        ($record->yearly_price ? ' | ৳' . number_format($record->yearly_price) . '/yr' : '')
                    )
                    ->badge()
                    ->color('gray'),

                TextColumn::make('trial_days')
                    ->label('Trial')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} Days Free" : 'None')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                TextColumn::make('limits_summary')
                    ->label('Limits (P / O / AI)')
                    ->getStateUsing(fn (Plan $record) =>
                        ($record->product_limit == 0 ? '∞' : $record->product_limit) . ' / ' .
                        ($record->order_limit == 0 ? '∞' : $record->order_limit) . ' / ' .
                        ($record->ai_message_limit == 0 ? '∞' : $record->ai_message_limit)
                    )
                    ->badge()
                    ->color('info'),

                // Feature Toggles as Icons
                IconColumn::make('allow_whatsapp')
                    ->label('WA')
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip('WhatsApp Bot'),

                IconColumn::make('allow_custom_domain')
                    ->label('Domain')
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip('Custom Domain'),

                IconColumn::make('allow_marketing_broadcast')
                    ->label('Broadcast')
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip('Marketing Broadcast'),

                IconColumn::make('priority_support')
                    ->label('Priority')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip('Priority Support'),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-s-fire')
                    ->trueColor('orange')
                    ->falseIcon('heroicon-o-fire')
                    ->tooltip('Featured Plan'),

                TextColumn::make('clients_count')
                    ->label('Subscribers')
                    ->counts('clients')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->disabled(fn () => !auth()->user()?->isSuperAdmin()),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->placeholder('All Plans'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->trueLabel('Featured Only')
                    ->falseLabel('Not Featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Plan $record) {
                        $new = $record->replicate();
                        $new->name = $record->name . ' (Copy)';
                        $new->is_active = false;
                        $new->sort_order = $record->sort_order + 1;
                        $new->save();
                    })
                    ->successNotificationTitle('Plan duplicated!'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Plan::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit'   => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}