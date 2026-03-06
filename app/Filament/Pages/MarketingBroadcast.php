<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Models\OrderSession;
use App\Models\Conversation;
use App\Services\Messenger\MessengerResponseService;

class MarketingBroadcast extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Marketing & Sales';
    protected static ?string $navigationLabel = 'Broadcast Message';
    protected static ?string $title = 'Marketing Broadcast';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.marketing-broadcast';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    // পেজে কোন ডাটা দেখাবে সুপার এডমিন বা ক্লায়েন্ট হিসেবে
    public static function canAccess(): bool
    {
        return true; 
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Create Broadcast Campaign')
                    ->description('আপনার কাস্টমারদের মেসেঞ্জারে সরাসরি প্রমোশনাল অফার বা ডিসকাউন্ট কোড পাঠান।')
                    ->schema([
                        Select::make('audience')
                            ->label('Target Audience (কাদের পাঠাবেন?)')
                            ->options([
                                'all' => 'All Customers (যারা পেজে আগে মেসেজ দিয়েছে)',
                                'abandoned' => 'Abandoned Carts (যারা প্রোডাক্ট দেখেও অর্ডার করেনি)',
                                'buyers' => 'Past Buyers (যাদের অর্ডার ডেলিভারি বা কমপ্লিট হয়েছে)',
                            ])
                            ->required()
                            ->default('all'),

                        Textarea::make('message')
                            ->label('Broadcast Message (আপনার অফার)')
                            ->placeholder("যেমন: 🎉 ধামাকা অফার! আজকেই যেকোনো প্রোডাক্ট অর্ডারে পাচ্ছেন ২০% ছাড়! অর্ডার করতে রিপ্লাই দিন।")
                            ->rows(5)
                            ->required()
                            ->helperText('নোট: এক ক্লিকে সবার ইনবক্সে এই মেসেজটি চলে যাবে।'),
                    ])
                    ->statePath('data'),
            ]);
    }

    public function sendBroadcast()
    {
        $data = $this->form->getState();
        $clientId = auth()->id() === 1 ? \App\Models\Client::first()?->id : auth()->user()->client->id ?? null;

        if (!$clientId) {
            Notification::make()->title('Error: Shop not found!')->danger()->send();
            return;
        }

        $client = \App\Models\Client::find($clientId);
        
        if (!$client || !$client->fb_page_token) {
            Notification::make()->title('Error: Facebook Page not connected!')->danger()->send();
            return;
        }

        $audience = $data['audience'];
        $message = $data['message'];
        
        $senderIds = collect();

        // অডিয়েন্স অনুযায়ী কাস্টমার আইডি (Sender ID) ফিল্টার করা
        if ($audience === 'all') {
            $senderIds = Conversation::where('client_id', $clientId)->pluck('sender_id')->unique();
        } elseif ($audience === 'abandoned') {
            $senderIds = OrderSession::where('client_id', $clientId)->where('customer_info->step', '!=', 'completed')->pluck('sender_id')->unique();
        } elseif ($audience === 'buyers') {
            $senderIds = OrderSession::where('client_id', $clientId)->where('customer_info->step', 'completed')->pluck('sender_id')->unique();
        }

        if ($senderIds->isEmpty()) {
            Notification::make()->title('No customers found in this category!')->warning()->send();
            return;
        }

        $successCount = 0;
        $messengerService = app(MessengerResponseService::class);

        // সবার কাছে লুপ চালিয়ে মেসেজ পাঠানো
        foreach ($senderIds as $senderId) {
            try {
                $messengerService->sendMessengerMessage($senderId, $message, $client->fb_page_token);
                $successCount++;
            } catch (\Exception $e) {
                // কোনো কারণে এক জায়গায় ফেইল হলে যেন লুপ বন্ধ না হয়
            }
        }

        // ফর্ম ক্লিয়ার করা
        $this->form->fill();

        Notification::make()
            ->title('🚀 Broadcast Sent Successfully!')
            ->body("মোট {$successCount} জন কাস্টমারের কাছে আপনার অফার পাঠানো হয়েছে।")
            ->success()
            ->send();
    }
}