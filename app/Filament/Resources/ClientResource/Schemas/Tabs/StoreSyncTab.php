<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

class StoreSyncTab
{
    public static function schema(): array
    {
        return [
            Section::make('Real-time AI Product Lookup (SaaS Feature)')
                ->description('আপনার কাস্টম বা ওয়ার্ডপ্রেস সাইটের সাথে রিয়েল-টাইম কানেকশন তৈরি করুন। AI আপনার সাইটের লাইভ ডেটা নিয়ে কাস্টমারকে উত্তর দিবে!')
                ->icon('heroicon-o-bolt')
                ->schema([
                    TextInput::make('external_api_url')
                        ->label('External API URL')
                        ->placeholder('e.g. https://yourwebsite.com/wp-json/ai-bot/v1/search')
                        ->helperText('এই লিংকে AI সার্চ কোয়ারি পাঠাবে (যেমন: ?q=shirt) এবং আপনার সার্ভার JSON রিটার্ন করবে।')
                        ->url(),
                    TextInput::make('external_product_api_key')
                        ->label('Secret API Key (Optional)')
                        ->password()
                        ->revealable()
                        ->placeholder('Bearer Token or Secret Key')
                        ->helperText('আপনার এন্ডপয়েন্ট প্রটেক্টেড থাকলে এখানে তার টোকেন দিন।'),
                ])->columns(2),

            Section::make('WooCommerce Sync (WordPress)')
                ->description('আপনার ওয়ার্ডপ্রেস ওয়েবসাইটের প্রোডাক্ট এক ক্লিকে এখানে ইমপোর্ট করুন।')
                ->collapsed()
                ->schema([
                    TextInput::make('wc_store_url')->label('Store URL')->url(),
                    TextInput::make('wc_consumer_key')->label('Consumer Key')->password()->revealable(),
                    TextInput::make('wc_consumer_secret')->label('Consumer Secret')->password()->revealable(),
                ])->columns(3),

            Section::make('Shopify Sync')
                ->description('আপনার শপিফাই স্টোরের প্রোডাক্ট ইমপোর্ট করুন।')
                ->collapsed()
                ->schema([
                    TextInput::make('shopify_store_url')->label('Shopify Store Domain')->placeholder('your-store.myshopify.com')->url(),
                    TextInput::make('shopify_access_token')->label('Admin API Access Token')->password()->revealable(),
                ])->columns(2),

            TextInput::make('api_token')
                ->label('Webhook API Token (WooCommerce/Shopify)')
                ->readOnly()
                ->suffixAction(
                    Action::make('copy_token')
                        ->icon('heroicon-m-clipboard')
                        ->color('success')
                        ->action(function ($livewire, $state) {
                            $livewire->js("window.navigator.clipboard.writeText('{$state}')");
                            Notification::make()->title('Token copied to clipboard!')->success()->send();
                        })
                )
                ->helperText('এই টোকেনটি WordPress এর webhook delivery URL এ ?api_key= এর পরে বসাবেন।'),
        ];
    }
}