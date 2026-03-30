<?php

namespace App\Filament\Resources\ClientResource\Schemas;

use App\Models\Client;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\ImageColumn;

class ClientTableSchema
{
    public static function columns(): array
    {
        return [
            ImageColumn::make('logo')
                ->circular()
                ->defaultImageUrl(url('/images/placeholder-shop.png'))
                ->label('Logo'),

            TextColumn::make('shop_name')
                ->searchable()
                ->weight('bold')
                ->sortable()
                ->copyable()
                ->description(fn (Client $record) => $record->custom_domain ?: $record->slug),
                
            TextColumn::make('plan.name')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Pro', 'Premium' => 'warning',
                    'Basic' => 'info',
                    default => 'gray',
                }),

            TextColumn::make('telegram_bot_token')
                ->label('Telegram')
                ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Setup Needed')
                ->badge()
                ->color(fn ($state) => $state ? 'success' : 'gray'),

            TextColumn::make('webhook_verified_at')
                ->label('Facebook')
                ->formatStateUsing(fn ($state) => $state ? 'Verified' : 'Pending')
                ->badge()
                ->color(fn ($state) => $state ? 'success' : 'danger')
                ->icon(fn ($state) => $state ? 'heroicon-m-check-badge' : 'heroicon-m-clock'),

            SelectColumn::make('status')
                ->label('Status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'suspended' => 'Suspended',
                ])
                ->visible(fn () => auth()->user()?->isSuperAdmin()),

            TextColumn::make('created_at')
                ->label('Member Since')
                ->since()
                ->tooltip(fn ($record) => $record->created_at->format('d M, Y'))
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->options(['active' => 'Active', 'inactive' => 'Inactive']),
        ];
    }

    public static function actions(): array
    {
        return [
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('Visit Shop')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (Client $record) => $record->custom_domain ? "https://{$record->custom_domain}" : route('shop.show', $record->slug))
                ->openUrlInNewTab(),
        ];
    }

    public static function bulkActions(): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make(),
        ];
    }
}