<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use App\Services\SmsService;

class SmsNotificationTab
{
    public static function schema(): array
    {
        return [
            Section::make('SMS Notification System')
                ->description('অর্ডার কনফার্মেশন, স্ট্যাটাস আপডেট এবং স্টক শেষ হওয়ার অ্যালার্ট SMS-এ পাঠান।')
                ->icon('heroicon-o-device-phone-mobile')
                ->schema([
                    Group::make([
                        Toggle::make('widgets.sms.enabled')
                            ->label('Enable SMS Notifications')
                            ->onColor('success')
                            ->offColor('gray')
                            ->live()
                            ->columnSpanFull(),

                        Select::make('widgets.sms.provider')
                            ->label('SMS Provider')
                            ->options([
                                'sslwireless' => 'SSL Wireless (Corporate)',
                                'mimsms'      => 'Mimsms / Mim IT',
                                'bulksmsbd'   => 'BulkSmsBD',
                                'custom'      => 'Custom Provider (API URL)',
                            ])
                            ->default('sslwireless')
                            ->required(fn (Get $get) => $get('widgets.sms.enabled'))
                            ->visible(fn (Get $get) => $get('widgets.sms.enabled'))
                            ->live(),

                        TextInput::make('widgets.sms.api_key')
                            ->label('API Key / Token')
                            ->password()
                            ->revealable()
                            ->required(fn (Get $get) => $get('widgets.sms.enabled'))
                            ->visible(fn (Get $get) => $get('widgets.sms.enabled')),

                        TextInput::make('widgets.sms.sender_id')
                            ->label('Masking Sender ID')
                            ->placeholder('e.g. DARAZ')
                            ->helperText('আপনার ব্র্যান্ডের নাম (Provider থেকে approved হতে হবে)')
                            ->visible(fn (Get $get) => $get('widgets.sms.enabled')),

                        TextInput::make('widgets.sms.custom_url')
                            ->label('Custom API URL Endpoint')
                            ->placeholder('https://api.yourprovider.com/sendsms?api_key={api_key}&senderid={sender_id}&number={phone}&message={message}')
                            ->helperText('Variables: {api_key}, {sender_id}, {phone}, {message}')
                            ->visible(fn (Get $get) => $get('widgets.sms.provider') === 'custom'),
                    ])->columns(['default' => 1, 'sm' => 2]),

                    Section::make('Notification Rules')
                        ->visible(fn (Get $get) => $get('widgets.sms.enabled'))
                        ->schema([
                            Toggle::make('widgets.sms.on_order_placed')
                                ->label('Order Placed (Customer)')
                                ->helperText('অর্ডার প্লেস হলে কাস্টমারকে কনফার্মেশন SMS পাঠানো হবে।'),
                                
                            Textarea::make('widgets.sms.order_placed_template')
                                ->label('Order Confirmation SMS Template')
                                ->default("আপনার অর্ডার #{order_id} নিশ্চিত হয়েছে!\nমোট: {currency}{total}\nট্র্যাক করুন: {track_url}")
                                ->helperText('Variables: {order_id}, {total}, {currency}, {track_url}, {shop_name}')
                                ->visible(fn (Get $get) => $get('widgets.sms.on_order_placed')),

                            Toggle::make('widgets.sms.on_status_change')
                                ->label('Status Update (Customer)')
                                ->helperText('অর্ডারের স্ট্যাটাস পরিবর্তন হলে SMS পাঠানো হবে (shipped, delivered, etc.)'),

                            Toggle::make('widgets.sms.on_low_stock')
                                ->label('Low Stock Alert (Admin)')
                                ->helperText('স্টক শেষ বা কমে গেলে আপনাকে (অ্যাডমিনকে) SMS অ্যালার্ট দেওয়া হবে।'),
                                
                            TextInput::make('widgets.sms.admin_phone')
                                ->label('Admin Phone Number')
                                ->placeholder('017XXXXXXXX')
                                ->helperText('এই নাম্বারে স্টক অ্যালার্ট পাঠানো হবে')
                                ->visible(fn (Get $get) => $get('widgets.sms.on_low_stock')),
                        ])->columns(1),

                    // Test SMS Action
                    Actions::make([
                        Action::make('test_sms')
                            ->label('Test SMS Connection')
                            ->color('info')
                            ->icon('heroicon-m-paper-airplane')
                            ->visible(fn (Get $get) => $get('widgets.sms.enabled'))
                            ->form([
                                TextInput::make('test_phone')
                                    ->label('Receiver Phone Number')
                                    ->required()
                                    ->placeholder('01XXXXXXXXX')
                            ])
                            ->action(function (array $data, $record, Get $get) {
                                // For test before save, we build fake client object with current form state
                                $client = $record ?: new \App\Models\Client();
                                $widgets = $client->widgets ?? [];
                                $widgets['sms'] = [
                                    'enabled'   => true,
                                    'provider'  => $get('widgets.sms.provider'),
                                    'api_key'   => $get('widgets.sms.api_key'),
                                    'sender_id' => $get('widgets.sms.sender_id'),
                                    'custom_url'=> $get('widgets.sms.custom_url'),
                                ];
                                $client->widgets = $widgets;
                                $client->shop_name = $record->shop_name ?? 'Test Shop';

                                $success = app(SmsService::class)->sendTest($client, $data['test_phone']);

                                if ($success) {
                                    Notification::make()->title('SMS Sent Successfully!')->success()->send();
                                } else {
                                    Notification::make()->title('Failed to send SMS')->body('Check API Key or SMS Provider portal.')->danger()->send();
                                }
                            })
                    ])->columnSpanFull()
                ]),
                
            Section::make('Stock Limit & Notify Rules')
                ->description('স্টক ম্যানেজমেন্ট এবং কাস্টমার নোটিফিকেশন সেটআপ।')
                ->icon('heroicon-o-archive-box-x-mark')
                ->collapsed()
                ->schema([
                    TextInput::make('widgets.stock_alert.threshold')
                        ->label('Low Stock Alert Threshold')
                        ->numeric()
                        ->default(5)
                        ->helperText('Product quantity এর নিচে গেলে Low Stock Alert পাবেন।'),
                    
                    Toggle::make('widgets.stock_alert.notify_me')
                        ->label('Show "Notify Me" to Customers')
                        ->default(true)
                        ->helperText('পণ্য Out of Stock হলে কাস্টমার স্টক আসার পর SMS পেতে রিকোয়েস্ট করতে পারবে।'),
                ])->columns(2),
        ];
    }
}
