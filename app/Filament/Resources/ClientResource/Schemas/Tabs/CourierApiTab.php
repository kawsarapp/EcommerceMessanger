<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\HtmlString;

class CourierApiTab
{
    public static function schema(): array
    {
        return [
            Section::make('📖 কুরিয়ার এপিআই নির্দেশিকা (Help Note)')
                ->description('অটোমেটিক পার্সেল এন্ট্রি এবং স্ট্যাটাস আপডেটের জন্য নিচের নিয়মগুলো মেনে চলুন।')
                ->schema([
                    Placeholder::make('instruction')
                        ->label('')
                        ->content(new HtmlString('
                            <ul class="list-disc pl-5 text-sm text-gray-600 bg-gray-50 p-4 rounded-lg">
                                <li><strong>API Key:</strong> আপনার কুরিয়ার প্যানেল (Steadfast/Pathao) থেকে API Key কপি করে নিচের ফর্মে বসান।</li>
                                <li><strong>অটো স্ট্যাটাস আপডেট:</strong> কুরিয়ার যখন পার্সেল ডেলিভারি করবে, ড্যাশবোর্ডে স্ট্যাটাস নিজে থেকেই আপডেট হওয়ার জন্য নিচের Webhook URL টি আপনার কুরিয়ার প্যানেলের Webhook সেটিংসে বসান।</li>
                                <li class="text-red-500"><strong>সতর্কতা:</strong> আপনার Webhook URL টি কাউকে শেয়ার করবেন না।</li>
                            </ul>
                        ')),
                    
                    TextInput::make('webhook_url_display')
                        ->label('Your Unique Steadfast Webhook URL')
                        // 🔥 FIX: default() er bodole formatStateUsing() dewa holo
                        ->formatStateUsing(fn ($record) => $record ? url("/api/webhook/courier/{$record->id}/steadfast") : 'Shop save korar por URL toiri hobe')
                        ->dehydrated(false) // 🔥 FIX: Eti database e save hobe na
                        ->readOnly()
                        ->suffixAction(
                            Action::make('copy')
                                ->icon('heroicon-m-clipboard')
                                ->action(fn ($livewire, $state) => $livewire->js("window.navigator.clipboard.writeText('{$state}')"))
                        ),
                ]),

            Section::make('Default Courier')
                ->description('অর্ডার শিপমেন্টের জন্য ডিফল্ট কুরিয়ার সিলেক্ট করুন।')
                ->schema([
                    Select::make('default_courier')
                        ->options([
                            'steadfast' => 'Steadfast Courier',
                            'pathao' => 'Pathao Courier',
                            'redx' => 'RedX Courier',
                        ])
                        ->placeholder('Select your preferred courier')
                        ->columnSpanFull(),
                ]),

            Section::make('Steadfast Setup')
                ->collapsed()
                ->icon('heroicon-o-key')
                ->schema([
                    TextInput::make('steadfast_api_key')
                        ->label('Api Key')
                        ->password()
                        ->revealable(),
                    TextInput::make('steadfast_secret_key')
                        ->label('Secret Key')
                        ->password()
                        ->revealable(),
                ])->columns(2),

            Section::make('Pathao Setup')
                ->collapsed()
                ->icon('heroicon-o-key')
                ->schema([
                    TextInput::make('pathao_api_key')
                        ->label('Client ID / API Key')
                        ->password()
                        ->revealable(),
                    TextInput::make('pathao_store_id')
                        ->label('Store ID'),
                ])->columns(2),

            Section::make('RedX Setup')
                ->collapsed()
                ->icon('heroicon-o-key')
                ->schema([
                    TextInput::make('redx_api_token')
                        ->label('API Access Token')
                        ->password()
                        ->revealable()
                        ->columnSpanFull(),
                ]),
        ];
    }
}