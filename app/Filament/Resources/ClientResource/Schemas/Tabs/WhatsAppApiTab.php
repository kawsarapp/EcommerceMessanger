<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;

class WhatsAppApiTab
{
    public static function schema(): array
    {
        return [
            Toggle::make('is_whatsapp_active')
                ->label('Enable WhatsApp AI Bot')
                ->helperText('আপনার কাস্টমারদের হোয়াটসঅ্যাপে অটোমেটিক রিপ্লাই দেওয়ার জন্য এটি চালু করুন।')
                ->onColor('success')
                ->live(),

            Radio::make('whatsapp_type')
                ->label('Select Connection Method')
                ->options([
                    'unofficial' => '📱 QR Code Scan (Free & Easy for Small Business)',
                    'official' => '🏢 Official Meta Cloud API (For Verified Business)',
                ])
                ->descriptions([
                    'unofficial' => 'আপনার ফোন থেকে হোয়াটসঅ্যাপ ওয়েব স্ক্যান করে কানেক্ট করুন। কোনো বিজনেস ভেরিফিকেশন লাগবে না।',
                    'official' => 'ফেসবুক ডেভেলপার প্যানেল থেকে টোকেন এনে বসান। ১০০% সিকিউর, তবে প্রতি মেসেজে মেটাকে পে করতে হবে।',
                ])
                ->visible(fn (Get $get) => $get('is_whatsapp_active'))
                ->live()
                ->required(fn (Get $get) => $get('is_whatsapp_active')),

            Section::make('QR Code Setup (Device Link)')
                ->visible(fn (Get $get) => $get('whatsapp_type') === 'unofficial' && $get('is_whatsapp_active'))
                ->schema([
                    Placeholder::make('qr_note')
                        ->label('Status')
                        ->content(fn ($record) => $record && $record->wa_status === 'connected' 
                            ? new HtmlString('<span class="text-green-600 font-bold">✅ WhatsApp is Connected! AI is ready to reply.</span>') 
                            : new HtmlString('<span class="text-red-500 font-bold">❌ Disconnected. Please connect your device.</span>')
                        ),
                        
                    Actions::make([
                        Action::make('generate_qr')
                            ->label('Generate QR Code')
                            ->icon('heroicon-o-qr-code')
                            ->color('info')
                            ->action(function ($record, Set $set) {
                                if (!$record) {
                                    Notification::make()->title('দয়া করে আগে শপটি Save করুন!')->warning()->send();
                                    return;
                                }
                                try {
                                    $instanceId = 'client_' . $record->id;
                                    $response = Http::post(config('services.whatsapp.api_url') . '/api/generate-qr', ['instance_id' => $instanceId]);
                                    if ($response->successful()) {
                                        $data = $response->json();
                                        if (isset($data['status']) && $data['status'] === 'connected') {
                                            $record->update(['wa_status' => 'connected', 'wa_instance_id' => $instanceId]);
                                            Notification::make()->title('Already Connected!')->success()->send();
                                        } elseif (isset($data['qr_code'])) {
                                            $record->update(['wa_instance_id' => $instanceId]);
                                            $set('generated_qr_code', $data['qr_code']);
                                            Notification::make()->title('QR Code Generated. Please Scan!')->success()->send();
                                        }
                                    } else {
                                        Notification::make()->title('Failed to get QR Code.')->danger()->send();
                                    }
                                } catch (\Exception $e) {
                                    Notification::make()->title('Error: Node Server is not running!')->danger()->send();
                                }
                            })
                            ->hidden(fn ($record) => $record && $record->wa_status === 'connected'),
                            
                        Action::make('disconnect_wa')
                            ->label('Disconnect & Rescan')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->action(function ($record, Set $set, \Livewire\Component $livewire) {
                                if ($record) {
                                    try {
                                        $instanceId = $record->wa_instance_id ?? ('client_' . $record->id);
                                        Http::post(config('services.whatsapp.api_url') . '/api/disconnect', ['instance_id' => $instanceId]);
                                    } catch (\Exception $e) {}
                                    
                                    $record->update(['wa_status' => 'disconnected', 'wa_instance_id' => null]);
                                    $set('generated_qr_code', null);
                                    
                                    Notification::make()->title('Disconnected successfully! You can now generate a new QR.')->warning()->send();
                                    $livewire->js('window.location.reload()');
                                }
                            })
                            ->visible(fn ($record) => $record && $record->wa_status === 'connected')
                    ]),
                    
                    Hidden::make('generated_qr_code')->dehydrated(false),
        
                    Placeholder::make('qr_display')
                        ->label('Scan this QR Code using WhatsApp')
                        ->visible(fn (Get $get) => $get('generated_qr_code') !== null)
                        ->content(fn (Get $get) => new HtmlString('
                            <div class="text-center bg-gray-50 p-6 rounded-2xl border border-gray-200 inline-block w-full max-w-sm">
                                <img src="' . $get('generated_qr_code') . '" style="width: 250px; height: 250px; margin: 0 auto; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);" />
                                <p class="text-sm text-gray-600 font-bold mt-4 animate-pulse">⏳ স্ক্যান করার জন্য অপেক্ষা করছি...</p>
                                <p class="text-xs text-gray-400 mt-1">আপনার মোবাইল থেকে Linked Devices এ গিয়ে স্ক্যান করুন।</p>
                            </div>
                        ')),
                ]),

            Section::make('Official Meta API Setup')
                ->visible(fn (Get $get) => $get('whatsapp_type') === 'official' && $get('is_whatsapp_active'))
                ->schema([
                    TextInput::make('wa_phone_number_id')
                        ->label('Phone Number ID')
                        ->placeholder('E.g. 102345678901234')
                        ->required(fn (Get $get) => $get('whatsapp_type') === 'official'),
                        
                    Textarea::make('wa_access_token')
                        ->label('Permanent Access Token')
                        ->placeholder('E.g. EAAGm0... ')
                        ->rows(3)
                        ->required(fn (Get $get) => $get('whatsapp_type') === 'official'),
                ])->columns(2),
        ];
    }
}