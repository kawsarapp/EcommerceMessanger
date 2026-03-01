<?php

namespace App\Filament\Resources\OrderResource\Schemas;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;

class OrderTableSchema
{
    public static function columns(): array
    {
        return [
            ImageColumn::make('customer_image')
                ->label('')
                ->circular()
                ->defaultImageUrl(url('/images/default-avatar.png')),

            TextColumn::make('customer_name')
                ->label('Customer')
                ->searchable()
                ->sortable()
                ->description(fn (Order $record): string => $record->customer_phone ?? ''),

            TextColumn::make('client.shop_name')
                ->label('Shop')
                ->toggleable(isToggledHiddenByDefault: auth()->id() !== 1),

            TextColumn::make('total_amount')
                ->label('Total')
                ->money('BDT')
                ->sortable()
                ->weight('bold'),

            SelectColumn::make('order_status')
                ->label('Status')
                ->options([
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ])
                ->selectablePlaceholder(false),

            TextColumn::make('payment_status')
                ->label('Payment')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'paid' => 'success',
                    'pending' => 'warning',
                    default => 'gray',
                }),

            TextColumn::make('created_at')
                ->label('Date')
                ->dateTime('d M, Y')
                ->sortable(),
        ];


    }

    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('order_status')
                ->label('Filter Status')
                ->options([
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ]),
        ];
    }

    public static function actions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]),
            // OrderTableSchema.php এর actions() মেথডের ভেতরে:
            Tables\Actions\Action::make('send_to_courier')
                ->label('Send to Courier')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->order_status === 'processing') // শুধু প্রসেসিং অর্ডারগুলো কুরিয়ারে পাঠানো যাবে
                ->action(function ($record) {
                    // এখানে আমরা Courier API Service কে কল করব
                    $result = app(\App\Services\Courier\CourierIntegrationService::class)->sendParcel($record);
                    
                    if ($result['status'] === 'success') {
                        \Filament\Notifications\Notification::make()
                            ->title('Parcel Sent to ' . ucfirst($record->client->default_courier))
                            ->success()
                            ->send();
                        
                        // স্ট্যাটাস আপডেট করে Shipped করে দেওয়া
                        $record->update(['order_status' => 'shipped']);
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Courier API Error')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),



        ];
    }

    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }
}