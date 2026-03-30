<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Models\User;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class StaffResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Staff Members';
    protected static ?string $navigationGroup = '⚙️ Settings & Tools';
    protected static ?string $modelLabel = 'Staff Member';
    protected static ?int $navigationSort = 1;

    // All available permissions a staff can be given
    public static function availablePermissions(): array
    {
        return [
            'view_orders'       => 'View Orders',
            'edit_orders'       => 'Edit Orders / Change Status',
            'delete_orders'     => 'Delete Orders',
            'export_orders'     => 'Export Orders to Google Sheet',
            'view_products'     => 'View Products',
            'edit_products'     => 'Add / Edit Products',
            'delete_products'   => 'Delete Products',
            'view_customers'    => 'View Customers / Conversations',
            'send_messages'     => 'Send Messages (Inbox)',
            'view_coupons'      => 'View & Manage Coupons',
            'view_reviews'      => 'View & Manage Reviews',
            'view_reports'      => 'View Sales Reports',
            'view_abandoned'    => 'View Abandoned Carts',
            'manage_basic_info' => 'Manage Shop Basic Info',
            'manage_storefront' => 'Manage Storefront Settings',
            'manage_domain_seo' => 'Manage Domain & SEO',
            'manage_ai_brain' => 'Manage AI Brain & Automation',
            'manage_logistics' => 'Manage Logistics',
            'manage_courier_api' => 'Manage Courier API',
            'manage_integrations' => 'Manage Integrations & Social',
            'manage_inbox_automation' => 'Manage Inbox Automation',
            'manage_store_sync' => 'Manage Store Sync',
            'manage_whatsapp' => 'Manage WhatsApp API Settings',
        ];
    }

    // Staff only sees users from their own shop; super admin sees all
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('role', 'staff');

        if (auth()->user()?->isSuperAdmin()) {
            return $query;
        }

        $clientId = auth()->user()?->client?->id;
        return $query->where('client_id', $clientId);
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $clientId = $user?->isSuperAdmin() ? null : $user?->client?->id;

        return $form->schema([
            Section::make('Staff Account')->schema([
                TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context) => $context === 'create')
                    ->helperText('Leave blank to keep existing password when editing.'),

                Toggle::make('is_active')
                    ->label('Account Active')
                    ->default(true),
            ])->columns(['sm' => 2]),

            Section::make('Permissions')->description('এই staff member কোন কাজগুলো করতে পারবে তা নির্ধারণ করুন।')->schema([
                CheckboxList::make('staff_permissions')
                    ->label('Allowed Actions')
                    ->options(self::availablePermissions())
                    ->columns(['sm' => 2])
                    ->bulkToggleable()
                    ->required(),
            ]),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = 'staff';
        if (!auth()->user()->isSuperAdmin()) {
            $data['client_id'] = auth()->user()->client?->id;
        }
        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Staff User')
                    ->weight('bold')
                    ->description(fn($record) => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),

                TextColumn::make('client.shop_name')
                    ->label('Shop')
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),

                TextColumn::make('staff_permissions')
                    ->label('Permissions')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'None';
                        $perms = self::availablePermissions();
                        $labels = array_map(fn($p) => $perms[$p] ?? $p, (array)$state);
                        return implode(', ', array_slice($labels, 0, 3)) . (count($labels) > 3 ? ' +' . (count($labels) - 3) . ' more' : '');
                    })
                    ->wrap()
                    ->limit(80),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('d M, Y'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit'   => Pages\EditStaff::route('/{record}/edit'),
        ];
    }

    // Sellers can always access their own staff page
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->role === 'staff') return false;
        if ($user->isSuperAdmin()) return true;
        $client = $user->client;
        return $client && $client->hasActivePlan();
    }
}
