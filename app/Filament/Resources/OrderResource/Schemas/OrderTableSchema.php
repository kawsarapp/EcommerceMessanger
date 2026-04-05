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
use Filament\Tables\Columns\Summarizers\Sum;
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
                ->weight('bold')
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
                ->summarize(Sum::make()->money('BDT')->label('Total Sale'))
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
                    'paid'    => 'success',
                    'pending' => 'warning',
                    'partial' => 'info',
                    'failed'  => 'danger',
                    default   => 'gray',
                })
                ->formatStateUsing(fn (string $state) => match ($state) {
                    'paid'    => '✅ Paid',
                    'pending' => '⏳ Pending',
                    'partial' => '💸 Partial',
                    'failed'  => '❌ Failed',
                    default   => ucfirst($state),
                })
                ->description(fn (Order $record): string =>
                    match ($record->payment_method) {
                        'bkash_pgw'      => '🔴 bKash (Official PGW)',
                        'bkash_merchant' => '📲 bKash Merchant',
                        'bkash_personal' => '📱 bKash Personal',
                        'sslcommerz'     => '💳 SSL Commerz',
                        'surjopay'       => '🌙 Surjopay',
                        'partial'        => '💸 Advance: ৳' . ($record->advance_amount ?? 0),
                        default          => '🚚 Cash on Delivery',
                    }
                ),

            TextColumn::make('admin_note')
                ->label('Note')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->limit(30)
                ->tooltip(fn ($record) => $record->admin_note)
                ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('created_at')
                ->label('Date')
                ->since()
                ->tooltip(fn ($record) => $record->created_at->format('d M, Y h:i A'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
        ];
    }

    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('order_status')
                ->label('Order Status')
                ->options([
                    'pending'    => 'Pending',
                    'processing' => 'Processing',
                    'shipped'    => 'Shipped',
                    'delivered'  => 'Delivered',
                    'cancelled'  => 'Cancelled',
                ]),

            Tables\Filters\SelectFilter::make('payment_status')
                ->label('Payment Status')
                ->options([
                    'pending' => '⏳ Pending',
                    'paid'    => '✅ Paid',
                    'partial' => '💸 Partial (Advance)',
                    'failed'  => '❌ Failed',
                ]),

            Tables\Filters\SelectFilter::make('payment_method')
                ->label('Payment Method')
                ->options([
                    'cod'            => '🚚 Cash on Delivery',
                    'bkash_pgw'      => '🔴 bKash PGW',
                    'bkash_merchant' => '📲 bKash Merchant',
                    'bkash_personal' => '📱 bKash Personal',
                    'sslcommerz'     => '💳 SSL Commerz',
                    'surjopay'       => '🌙 Surjopay',
                    'partial'        => '💸 Partial Payment',
                    'full'           => '✅ Full Pre-Payment',
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
                            ->title('⚠️ Invalid Phone Number')
                            ->body('This order has no valid phone number to check.')
                            ->warning()
                            ->send();
                        return;
                    }

                    try {
                        $response = \Illuminate\Support\Facades\Http::withToken(config('services.bdcourier.api_key'))
                            ->timeout(10)
                            ->post('https://api.bdcourier.com/courier-check', [
                                'phone' => $phone,
                            ]);

                        $json = $response->json();

                        // ── API Error ──────────────────────────────────────────────
                        if (!$response->successful() || ($json['status'] ?? '') !== 'success') {
                            \Filament\Notifications\Notification::make()
                                ->title('❌ API Error')
                                ->body('BDCourier returned an error: ' . ($json['message'] ?? 'Unknown error'))
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // ── Parse response ─────────────────────────────────────────
                        $courierData = $json['data'] ?? [];          // new format key: 'data'
                        $reports     = $json['reports'] ?? [];
                        $summary     = $courierData['summary'] ?? null;

                        // Remove 'summary' from per-courier iteration
                        $couriers = collect($courierData)->except('summary')->filter(fn($c) => is_array($c));

                        // ── No data at all ────────────────────────────────────────
                        if ($couriers->isEmpty() && empty($reports)) {
                            \Filament\Notifications\Notification::make()
                                ->title('✅ Clean Record')
                                ->body("Phone {$phone} has no courier history. Likely a new or low-risk customer.")
                                ->success()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // ── Risk Calculation ──────────────────────────────────────
                        $reportCount  = count($reports);
                        $totalCancelled = $summary['cancelled_parcel'] ?? $couriers->sum(fn($c) => $c['cancelled_parcel'] ?? 0);
                        $overallRatio   = (float) ($summary['success_ratio'] ?? 100.0);
                        $totalParcels   = (int)   ($summary['total_parcel'] ?? $couriers->sum(fn($c) => $c['total_parcel'] ?? 0));

                        // High-risk courier: needs ENOUGH sample (≥5 parcels) AND low success (<60%)
                        // Small sample couriers (< 5 parcels) are flagged with ⚠️ info only — not risk factor
                        $significantHighRisk = $couriers->filter(
                            fn($c) => ($c['total_parcel'] ?? 0) >= 5 && ($c['success_ratio'] ?? 100) < 60
                        );
                        // Moderate-risk courier: ≥5 parcels AND success 60-75%
                        $moderateRiskCouriers = $couriers->filter(
                            fn($c) => ($c['total_parcel'] ?? 0) >= 5 && ($c['success_ratio'] ?? 100) < 75
                        );

                        // 🔴 HIGH RISK: fraud reports exist, OR very low overall ratio, OR many cancellations, OR multiple significant high-risk couriers
                        if ($reportCount > 0 || $overallRatio < 50 || $totalCancelled >= 20 || $significantHighRisk->count() >= 2) {
                            $riskIcon  = '🔴';
                            $riskLabel = 'HIGH RISK';
                            $notifType = 'danger';
                        // 🟡 MODERATE: overall ratio 50-75%, OR 5+ cancellations, OR 1 significant high-risk / moderate-risk courier
                        } elseif ($overallRatio < 75 || $totalCancelled >= 5 || $significantHighRisk->count() >= 1 || $moderateRiskCouriers->count() >= 1) {
                            $riskIcon  = '🟡';
                            $riskLabel = 'MODERATE RISK';
                            $notifType = 'warning';
                        // 🟢 LOW RISK: everything else
                        } else {
                            $riskIcon  = '🟢';
                            $riskLabel = 'LOW RISK';
                            $notifType = 'success';
                        }

                        // ── Build Summary Body ────────────────────────────────────
                        $lines = [];
                        $lines[] = "📞 Phone: {$phone}";
                        $lines[] = "📦 Total Parcels: {$totalParcels} | ✅ Success: " . ($summary['success_parcel'] ?? '-') . " | ❌ Cancelled: {$totalCancelled}";
                        $lines[] = "📊 Overall Success Rate: {$overallRatio}%";
                        $lines[] = '';

                        // Per-courier breakdown (only couriers with activity)
                        $activeCouriers = $couriers->filter(fn($c) => ($c['total_parcel'] ?? 0) > 0);
                        if ($activeCouriers->isNotEmpty()) {
                            $lines[] = "🚚 Courier Breakdown:";
                            foreach ($activeCouriers as $c) {
                                $ratio  = $c['success_ratio'] ?? 0;
                                $total  = $c['total_parcel'] ?? 0;
                                // Only flag low ratio if sample is meaningful (≥5 parcels)
                                if ($total < 5) {
                                    $emoji = 'ℹ️'; // small sample — no strong conclusion
                                } elseif ($ratio >= 75) {
                                    $emoji = '✅';
                                } elseif ($ratio >= 60) {
                                    $emoji = '⚠️';
                                } else {
                                    $emoji = '🔴';
                                }
                                $lines[] = "  {$emoji} {$c['name']}: {$total} parcels | {$ratio}% success | {$c['cancelled_parcel']} cancelled" . ($total < 5 ? ' (small sample)' : '');
                            }
                        }

                        // Fraud reports
                        if (!empty($reports)) {
                            $lines[] = '';
                            $lines[] = "🚨 Fraud Reports ({$reportCount}):";
                            foreach ($reports as $rep) {
                                $lines[] = "  • [{$rep['courierName']}] {$rep['name']}: {$rep['details']}";
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title("{$riskIcon} {$riskLabel} — BDCourier Check")
                            ->body(implode("\n", $lines))
                            ->{$notifType}()
                            ->persistent()
                            ->send();

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('❌ API Unreachable')
                            ->body('BDCourier API error: ' . $e->getMessage())
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