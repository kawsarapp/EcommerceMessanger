<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourierReportResource\Pages;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

class CourierReportResource extends Resource
{
    // Amra Order model kei use korbo, kintu sudhu courier er data dekhabo
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Courier Reports';
    protected static ?string $slug = 'courier-reports';
    protected static ?string $navigationGroup = 'Logistics & Reports';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->isStaff()) {
            if (!$user->client || !$user->client->hasActivePlan() || !$user->client->canAccessFeature('allow_delivery_integration')) {
                return false;
            }
            return $user->hasStaffPermission('view_reports');
        }

        $client = $user->client;
        if (!$client || !$client->hasActivePlan()) return false;

        return $client->canAccessFeature('allow_delivery_integration');
    }

    // 100% Data Isolation (Seller sudhu nijer data dekhbe)
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->whereNotNull('courier_name'); // Sudhu courier a pathano order gulo asbe
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            return $query; // Admin sob dekhbe
        }

        $clientId = $user?->client ? $user->client->id : ($user?->client_id ?? null);
        return $query->where('client_id', $clientId);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Order ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Order $record): string => $record->customer_phone ?? ''),

                TextColumn::make('courier_name')
                    ->label('Courier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'steadfast' => 'success',
                        'pathao' => 'danger',
                        'redx' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('tracking_code')
                    ->label('Tracking Code')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-clipboard-document'),

                TextColumn::make('order_status')
                    ->label('Current Status')
                    ->badge(),

                TextColumn::make('updated_at')
                    ->label('Sent on')
                    ->dateTime('d M, Y h:i A')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('courier_name')
                    ->label('Filter by Courier')
                    ->options([
                        'steadfast' => 'Steadfast',
                        'pathao' => 'Pathao',
                        'redx' => 'RedX',
                    ]),
                
                // Date Filter
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('From Date'),
                        DatePicker::make('created_until')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn (Builder $query, $date) => $query->whereDate('updated_at', '>=', $date))
                            ->when($data['created_until'], fn (Builder $query, $date) => $query->whereDate('updated_at', '<=', $date));
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // Sudhu view korbe, edit korar dorkar nai ekhane
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourierReports::route('/'),
        ];
    }

    // Ei theke notun order create kora jabe na, eta sudhu report
    public static function canCreate(): bool
    {
        return false;
    }
}