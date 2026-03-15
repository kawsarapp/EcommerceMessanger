<?php

namespace App\Filament\Resources\ClientResource\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;

// 🔥 আমরা যে নতুন ফোল্ডারটি বানিয়েছি, সেটি ইম্পোর্ট করছি
use App\Filament\Resources\ClientResource\Schemas\Tabs as ClientTabs;

class ClientFormSchema
{
    public static function schema(): array
    {
        $isAdmin = fn () => auth()->user()?->isSuperAdmin() ?? false;
        $isNotAdmin = fn () => !auth()->user()?->isSuperAdmin();

        return [
            Section::make('Subscription Plan')
                ->description('User subscription & limitations control.')
                ->icon('heroicon-m-credit-card')
                ->collapsible()
                ->schema([
                    Select::make('plan_id')
                        ->label('Assigned Plan')
                        ->relationship('plan', 'name')
                        ->preload()
                        ->searchable()
                        ->required($isAdmin)
                        ->disabled($isNotAdmin)
                        ->dehydrated($isAdmin),

                    DateTimePicker::make('plan_ends_at')
                        ->label('Plan Expiry Date')
                        ->default(now()->addMonth())
                        ->required($isAdmin)
                        ->disabled($isNotAdmin)
                        ->dehydrated($isAdmin),
                ])
                ->columns(['default' => 1, 'sm' => 2])
                ->visible($isAdmin),

            // 🔥 ম্যাজিক: সবগুলো আলাদা ফাইল থেকে কল হচ্ছে!
            Group::make()->schema([
                Tabs::make('Shop Configuration')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Basic Info')->icon('heroicon-m-information-circle')->schema(ClientTabs\BasicInfoTab::schema()),
                        Tab::make('Storefront')->icon('heroicon-m-paint-brush')->schema(ClientTabs\StorefrontTab::schema()),
                        Tab::make('Domain & SEO')->icon('heroicon-m-globe-alt')->schema(ClientTabs\DomainSeoTab::schema()),
                        Tab::make('AI Brain & Automation')->icon('heroicon-m-cpu-chip')->schema(ClientTabs\AiBrainTab::schema()),
                        Tab::make('Logistics')->icon('heroicon-m-truck')->schema(ClientTabs\LogisticsTab::schema()),
                        Tab::make('Courier API')->icon('heroicon-m-archive-box-arrow-down')->schema(ClientTabs\CourierApiTab::schema()),
                        Tab::make('Integrations & Social')->icon('heroicon-m-share')->schema(ClientTabs\IntegrationsTab::schema()),
                        Tab::make('Inbox Automation')->icon('heroicon-m-chat-bubble-left-right')->schema(ClientTabs\InboxAutomationTab::schema()),
                        Tab::make('Store Sync')->icon('heroicon-m-arrow-path-rounded-square')->schema(ClientTabs\StoreSyncTab::schema()),
                        Tab::make('WhatsApp API')->icon('heroicon-m-chat-bubble-oval-left-ellipsis')->schema(ClientTabs\WhatsAppApiTab::schema()),
                        Tab::make('🔑 Admin Permissions')->icon('heroicon-m-shield-check')->schema(ClientTabs\AdminPermissionsTab::schema())->visible(fn () => auth()->user()?->isSuperAdmin()),
                    ])
                    ->columnSpanFull(),
            ])->columnSpanFull(),
        ];
    }
}