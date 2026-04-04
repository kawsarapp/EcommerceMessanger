<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\Schemas\ClientFormSchema;
use App\Filament\Resources\ClientResource\Schemas\ClientTableSchema;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\Page;
use App\Filament\Resources\ClientResource\Schemas\Tabs\BasicInfoTab;
use App\Filament\Resources\ClientResource\Schemas\Tabs\StorefrontTab;
use App\Filament\Resources\ClientResource\Schemas\Tabs\PaymentGatewaysTab;
use App\Filament\Resources\ClientResource\Schemas\Tabs\DomainSeoTab;
use App\Filament\Resources\ClientResource\Schemas\Tabs\FeaturesTab;
use App\Filament\Resources\ClientResource\Schemas\Tabs\IntegrationsTab;
use App\Filament\Resources\ClientResource\Schemas\Tabs\InboxAutomationTab;
use App\Filament\Resources\ClientResource\Schemas\Tabs\WhatsAppApiTab;
use App\Filament\Resources\ClientResource\Schemas\Tabs\AdminPermissionsTab;
use App\Filament\Resources\ClientResource\Schemas\Tabs\StoreSyncTab;
use App\Filament\Resources\ClientResource\Schemas\Tabs\SmsNotificationTab;
use Filament\Forms\Components\Tabs;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    
    protected static ?string $navigationGroup = 'Shop Management';
    
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isSuperAdmin() ? 'Clients' : 'My Shop Details';
    }

    public static function getNavigationUrl(): string
    {
        $user = auth()->user();
        if ($user && !$user->isSuperAdmin()) {
            $clientId = $user->client_id ?? $user->client?->id;
            if ($clientId) {
                return static::getUrl('edit', ['record' => $clientId]);
            }
        }

        return parent::getNavigationUrl();
    }

    public static function getNavigationBadge(): ?string
    {
        return auth()->user()?->isSuperAdmin() ? (string) static::getModel()::count() : null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['shop_name', 'slug', 'fb_page_id', 'custom_domain', 'phone'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Client Setup')
                ->tabs([
                    Tabs\Tab::make('Profile')->schema(BasicInfoTab::schema())->icon('heroicon-o-user')->iconPosition('before'),
                    Tabs\Tab::make('Storefront')->schema(StorefrontTab::schema())->icon('heroicon-o-shopping-bag')->iconPosition('before'),
                    Tabs\Tab::make('Payments')->schema(PaymentGatewaysTab::schema())->icon('heroicon-o-credit-card')->iconPosition('before'),
                    Tabs\Tab::make('Domain & SEO')->schema(DomainSeoTab::schema())->icon('heroicon-o-globe-alt')->iconPosition('before'),
                    Tabs\Tab::make('Omnichannel')->schema(IntegrationsTab::schema())->icon('heroicon-o-chat-bubble-left-right')->iconPosition('before'),
                    Tabs\Tab::make('SMS & Alerts')->schema(SmsNotificationTab::schema())->icon('heroicon-o-device-phone-mobile')->iconPosition('before'),
                    Tabs\Tab::make('Inbox Auto')->schema(InboxAutomationTab::schema())->icon('heroicon-o-inbox-stack')->iconPosition('before'),
                    Tabs\Tab::make('WhatsApp API')->schema(WhatsAppApiTab::schema())->icon('heroicon-o-chat-bubble-oval-left-ellipsis')->iconPosition('before'),
                    Tabs\Tab::make('Features')->schema(FeaturesTab::schema())->icon('heroicon-o-sparkles')->iconPosition('before'),
                    Tabs\Tab::make('Sync')->schema(StoreSyncTab::schema())->icon('heroicon-o-arrow-path')->iconPosition('before'),
                    Tabs\Tab::make('Permissions')->schema(AdminPermissionsTab::schema())->icon('heroicon-o-shield-check')->iconPosition('before'),
                ])->columnSpanFull()->persistTabInQueryString('client-tab')
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ClientTableSchema::columns())
            ->defaultSort('created_at', 'desc')
            ->filters(ClientTableSchema::filters())
            ->actions(ClientTableSchema::actions())
            ->bulkActions(ClientTableSchema::bulkActions());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            return $query;
        }
        
        $clientId = $user?->client ? $user->client->id : ($user?->client_id ?? null);
        return $query->where('id', $clientId);
    }
    
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditBasicInfo::class,
            Pages\EditStorefront::class,
            Pages\EditDomainSeo::class,
            Pages\EditAiBrain::class,
            Pages\EditLogistics::class,
            Pages\EditCourierApi::class,
            Pages\EditPaymentGateways::class,
            Pages\EditIntegrations::class,
            Pages\EditInboxAutomation::class,
            Pages\EditStoreSync::class,
            Pages\EditWhatsAppApi::class,
            Pages\EditAdminPermissions::class,
            Pages\EditFeatures::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditBasicInfo::route('/{record}/edit'), // Default Edit Route points to Basic Info
            'storefront' => Pages\EditStorefront::route('/{record}/storefront'),
            'domain-seo' => Pages\EditDomainSeo::route('/{record}/domain-seo'),
            'ai-brain' => Pages\EditAiBrain::route('/{record}/ai-brain'),
            'logistics' => Pages\EditLogistics::route('/{record}/logistics'),
            'courier-api' => Pages\EditCourierApi::route('/{record}/courier-api'),
            'payment-gateways' => Pages\EditPaymentGateways::route('/{record}/payment-gateways'),
            'integrations' => Pages\EditIntegrations::route('/{record}/integrations'),
            'inbox-automation' => Pages\EditInboxAutomation::route('/{record}/inbox-automation'),
            'store-sync' => Pages\EditStoreSync::route('/{record}/store-sync'),
            'whatsapp-api' => Pages\EditWhatsAppApi::route('/{record}/whatsapp-api'),
            'admin-permissions' => Pages\EditAdminPermissions::route('/{record}/admin-permissions'),
            'features' => Pages\EditFeatures::route('/{record}/features'),
        ];
    }

    // --- Permissions ---
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Allow staff to view resource if they have at least one permission
        if ($user->isStaff()) {
            $perms = [
                'manage_basic_info', 'manage_storefront', 'manage_domain_seo',
                'manage_ai_brain', 'manage_logistics', 'manage_courier_api',
                'manage_integrations', 'manage_inbox_automation', 'manage_store_sync',
                'manage_whatsapp'
            ];
            foreach ($perms as $perm) {
                if ($user->hasStaffPermission($perm)) return true;
            }
            return false;
        }

        return true;
    }

    public static function canCreate(): bool 
    { 
        return auth()->user()?->isSuperAdmin() ?? false; 
    } 
    
    public static function canDelete(Model $record): bool 
    { 
        return auth()->user()?->isSuperAdmin() ?? false; 
    }
    
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->role === 'staff') {
            return $record->id === $user->client_id;
        }

        return $record->user_id === $user->id;
    }
}