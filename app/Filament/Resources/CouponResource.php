<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket'; // কুপনের জন্য টিকিটের আইকন
    
    protected static ?string $navigationGroup = '🛍️ Products & Catalog';
    protected static ?int $navigationSort = 4;

    /**
     * ডাটা আইসোলেশন: ক্লায়েন্ট শুধুমাত্র নিজের কুপন দেখবে।
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        $client = $user->client;
        if (!$client) return false;

        // canAccessFeature() checks admin override first, then plan
        if (!$client->canAccessFeature('allow_coupon')) return false;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('view_coupons');
        }

        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        if ($user?->isSuperAdmin()) {
            return $query;
        }

        $clientId = $user?->client ? $user->client->id : ($user?->client_id ?? null);
        return $query->where('client_id', $clientId);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Coupon Details')
                    ->schema([
                        // Hidden Field: নিজে থেকেই ইউজারের Client ID সেভ করবে
                        Forms\Components\Hidden::make('client_id')
                            ->default(fn () => auth()->user()->isSuperAdmin() ? 1 : (auth()->user()->client?->id ?? auth()->user()->client_id))
                            ->required(),

                        Forms\Components\TextInput::make('code')
                            ->label('Coupon Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->helperText('Example: EID2026, SUMMER50'),

                        Forms\Components\Select::make('type')
                            ->label('Discount Type')
                            ->options([
                                'fixed' => 'Fixed Amount (৳)',
                                'percent' => 'Percentage (%)',
                            ])
                            ->default('fixed')
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label(fn (\Filament\Forms\Get $get) => $get('type') === 'percent' ? 'Discount Percentage (%)' : 'Discount Amount (৳)')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('min_spend')
                            ->label('Minimum Spend (Optional)')
                            ->numeric()
                            ->prefix('৳')
                            ->helperText('Minimum cart value required to use this coupon.'),

                        Forms\Components\TextInput::make('usage_limit')
                            ->label('Usage Limit (Optional)')
                            ->numeric()
                            ->helperText('How many times can this coupon be used in total?'),

                        Forms\Components\DatePicker::make('expires_at')
                            ->label('Expiry Date (Optional)')
                            ->minDate(now()),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->weight('bold')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Coupon code copied!'),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'success',
                        'percent' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($record) => $record->type === 'percent' ? $record->discount_amount . '%' : '৳' . $record->discount_amount),

                Tables\Columns\TextColumn::make('used_count')
                    ->label('Used')
                    ->formatStateUsing(fn ($record) => $record->used_count . ($record->usage_limit ? ' / ' . $record->usage_limit : '')),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date('d M, Y')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        $client = $user->client;
        if (!$client) return false;

        if (!$client->canAccessFeature('allow_coupon')) return false;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('view_coupons');
        }

        return true;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('view_coupons') && $user->client_id === $record->client_id;
        }

        return $user->client && $user->client->id === $record->client_id && $user->client->hasActivePlan();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('view_coupons') && $user->client_id === $record->client_id;
        }

        return $user->client && $user->client->id === $record->client_id;
    }
}