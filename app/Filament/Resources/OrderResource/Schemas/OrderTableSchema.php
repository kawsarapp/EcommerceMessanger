<?php

namespace App\Filament\Resources\OrderResource\Schemas;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Collection;

class OrderTableSchema
{
    public static function columns(): array
    {
        return [
            // 🔥 Customer Image Column Remove করা হয়েছে
            
            TextColumn::make('customer_name')
                ->label('Customer')
                ->searchable()
                ->sortable()
                ->description(fn (Order $record): string => $record->customer_phone ?? ''),

            // ওয়েবসাইটের ডাইরেক্ট চেকআউট থেকে এসেছে কিনা তা দেখানোর জন্য
            IconColumn::make('is_guest_checkout')
                ->label('Source')
                ->icon(fn (string $state): string => match ($state) {
                    '1' => 'heroicon-o-globe-alt', // Website
                    '0' => 'heroicon-o-chat-bubble-oval-left-ellipsis', // Messenger/WhatsApp
                    default => 'heroicon-o-question-mark-circle',
                })
                ->color(fn (string $state): string => match ($state) {
                    '1' => 'info',
                    '0' => 'success',
                    default => 'gray',
                })
                ->tooltip(fn ($state) => $state ? 'Ordered via Website Checkout' : 'Ordered via Chatbot'),

            TextColumn::make('client.shop_name')
                ->label('Shop')
                ->toggleable(isToggledHiddenByDefault: auth()->id() !== 1),

            // কুপন কোড
            TextColumn::make('coupon_code')
                ->label('Coupon')
                ->badge()
                ->color('success')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false)
                ->default('None'),

            TextColumn::make('total_amount')
                ->label('Total')
                ->money('BDT')
                ->sortable()
                ->weight('bold')
                ->description(fn (Order $record): string => $record->discount_amount > 0 ? "Saved: ৳{$record->discount_amount}" : ""),

            SelectColumn::make('order_status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
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
                ->dateTime('d M, Y h:i A')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('order_status')
                ->label('Filter Status')
                ->options([
                    'pending' => 'Pending',
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
            Action::make('print_invoice')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn (Order $record) => route('orders.print', $record))
                ->openUrlInNewTab(),

            Action::make('send_to_courier')
                ->label('Send to Courier')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                // 🔥 FIX: এখন pending এবং processing দুই অবস্থাতেই বাটন শো করবে
                ->visible(fn ($record) => in_array($record->order_status, ['pending', 'processing'])) 
                ->action(function ($record) {
                    $result = app(\App\Services\Courier\CourierIntegrationService::class)->sendParcel($record);
                    
                    if ($result['status'] === 'success') {
                        \Filament\Notifications\Notification::make()
                            ->title('Parcel Sent to ' . ucfirst($record->client->default_courier ?? 'Courier'))
                            ->success()
                            ->send();
                        
                        $record->update(['order_status' => 'shipped']);
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Courier API Error')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),

            ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])->icon('heroicon-m-ellipsis-vertical'),
        ];
    }

    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                
                // 🔥 NEW FEATURE: Bulk Status Update
                Tables\Actions\BulkAction::make('mark_as_processing')
                    ->label('Mark as Processing')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->update(['order_status' => 'processing'])),
                    
                Tables\Actions\BulkAction::make('mark_as_shipped')
                    ->label('Mark as Shipped')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->update(['order_status' => 'shipped'])),
            ]),
        ];
    }
}