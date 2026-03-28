<?php

namespace App\Filament\Resources\OrderResource\Schemas;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

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

            TextColumn::make('admin_note')
                ->label('Note')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->limit(30)
                ->tooltip(fn ($record) => $record->admin_note)
                ->toggleable(isToggledHiddenByDefault: false),

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
                ->visible(fn ($record) => in_array($record->order_status, ['pending', 'processing']) && empty($record->tracking_code)) 
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

            Action::make('sync_courier')
                ->label('Sync Status')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn ($record) => !empty($record->tracking_code) && !empty($record->courier_name) && !in_array($record->order_status, ['delivered', 'cancelled']))
                ->action(function ($record) {
                    $result = app(\App\Services\Courier\CourierIntegrationService::class)->syncStatus($record);
                    if ($result['status'] === 'success') {
                        \Filament\Notifications\Notification::make()
                            ->title('Live Status Synced')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Sync Failed')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),

            // 🔥 Add / Edit Note
            Action::make('add_note')
                ->label('Note')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->form([
                    Textarea::make('admin_note')
                        ->label('Order Note (internal)')
                        ->placeholder('Add internal notes about this order...')
                        ->default(fn (Order $record) => $record->admin_note)
                        ->rows(4)
                        ->maxLength(1000),
                ])
                ->action(function (Order $record, array $data) {
                    $record->update(['admin_note' => $data['admin_note']]);
                    \Filament\Notifications\Notification::make()
                        ->title('Note saved!')
                        ->success()
                        ->send();
                }),

            Action::make('fraud_check')
                ->label('Fraud Check')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->action(function (Order $record) {
                    $phone = preg_replace('/[^0-9]/', '', $record->customer_phone ?? '');
                    
                    if (strlen($phone) < 10) {
                        \Filament\Notifications\Notification::make()
                            ->title('Invalid Phone Number')
                            ->body('This order has no valid phone number to check.')
                            ->warning()
                            ->send();
                        return;
                    }

                    try {
                        $response = \Illuminate\Support\Facades\Http::withToken(config('services.bdcourier.api_key'))
                            ->timeout(8)
                            ->post('https://api.bdcourier.com/courier-check', [
                                'phone' => $phone
                            ]);

                        $data = $response->json();

                        if ($response->successful() && ($data['status'] ?? '') === 'success') {
                            $couriers = $data['data']['couriers'] ?? [];

                            if (empty($couriers)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('✅ No Courier Record Found')
                                    ->body("Phone {$phone} has no courier history. Likely a new or low-risk customer.")
                                    ->success()
                                    ->persistent()
                                    ->send();
                                return;
                            }

                            $activeCouriers = collect($couriers)->where('status', 'active')->pluck('name')->join(', ');
                            $allNames = collect($couriers)->pluck('name')->join(', ');
                            $count = count($couriers);

                            $riskLevel = $count >= 3 ? '🔴 HIGH RISK' : ($count >= 1 ? '🟡 MODERATE' : '🟢 LOW RISK');

                            \Filament\Notifications\Notification::make()
                                ->title("{$riskLevel} — {$count} Courier(s) Found")
                                ->body("Phone: {$phone}\nActive on: {$activeCouriers}\nAll records: {$allNames}")
                                ->warning()
                                ->persistent()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('✅ Clean Record')
                                ->body("Phone {$phone} has no known courier fraud record.")
                                ->success()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('API Error')
                            ->body('BDCourier API unreachable: ' . $e->getMessage())
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

                // Bulk Status Update
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

                Tables\Actions\BulkAction::make('sync_courier_bulk')
                    ->label('Sync API Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (Collection $records) {
                        $synced = 0;
                        foreach ($records as $record) {
                            if (!empty($record->tracking_code) && !in_array($record->order_status, ['delivered', 'cancelled'])) {
                                $res = app(\App\Services\Courier\CourierIntegrationService::class)->syncStatus($record);
                                if ($res['status'] === 'success') $synced++;
                            }
                        }
                        \Filament\Notifications\Notification::make()
                            ->title("Synced {$synced} Orders")
                            ->success()
                            ->send();
                    }),

                // 📊 Google Sheet Export
                Tables\Actions\BulkAction::make('export_google_sheet')
                    ->label('Export to Google Sheet (CSV)')
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->action(function (Collection $records) {
                        $csvRows = [["Order ID", "Customer", "Phone", "Address", "Items", "Total", "Discount", "Coupon", "Status", "Payment", "Note", "Date"]];
                        foreach ($records as $order) {
                            $items = $order->orderItems->map(fn($i) => $i->product_name . ' x' . $i->quantity)->implode(' | ');
                            $csvRows[] = [
                                $order->id,
                                $order->customer_name,
                                $order->customer_phone,
                                $order->customer_address,
                                $items,
                                $order->total_amount,
                                $order->discount_amount ?? 0,
                                $order->coupon_code ?? '',
                                $order->order_status,
                                $order->payment_status,
                                $order->admin_note ?? '',
                                $order->created_at->format('Y-m-d H:i'),
                            ];
                        }
                        $csv = implode("\n", array_map(fn($r) => implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $r)), $csvRows));
                        $filename = 'orders-export-' . now()->format('Y-m-d') . '.csv';

                        // Return downloadable response
                        return response()->streamDownload(fn() => print($csv), $filename, [
                            'Content-Type' => 'text/csv',
                        ]);
                    })
                    ->deselectRecordsAfterCompletion(),
            ]),
        ];
    }
}