<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Conversation;
use App\Models\OrderSession;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use App\Services\Messenger\MessengerResponseService;

class Inbox extends Page
{
    protected static bool $shouldRegisterNavigation = true;
    protected static ?int $navigationSort = 1; 
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Customer Support';
    protected static ?string $navigationLabel = 'Live Inbox';
    protected static ?string $title = 'Messenger Inbox';

    protected static string $view = 'filament.pages.inbox';

    public $clientId;
    public $senders = [];
    public $selectedSender = null;
    public $chatHistory = [];
    public $isAiActive = true;
    public $newMessage = ''; 

    public static function canAccess(): bool
    {
        return true; 
    }

    public function mount()
    {
        $user = Auth::user();
        $this->clientId = $user->id === 1 ? Client::first()?->id : $user->client?->id;
        
        $this->loadSenders();
    }

    public function loadSenders()
    {
        if (!$this->clientId) return;

        $this->senders = Conversation::where('client_id', $this->clientId)
            ->whereIn('id', function($query) {
                $query->selectRaw('MAX(id)')->from('conversations')->groupBy('sender_id');
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function selectSender($senderId)
    {
        $this->selectedSender = $senderId;
        $this->loadChat();
        
        $session = OrderSession::where('client_id', $this->clientId)->where('sender_id', $senderId)->first();
        $this->isAiActive = $session ? !$session->is_human_agent_active : true;
    }

    public function loadChat()
    {
        if ($this->selectedSender) {
            $this->chatHistory = Conversation::where('client_id', $this->clientId)
                ->where('sender_id', $this->selectedSender)
                ->orderBy('created_at', 'asc')
                ->get();
        }
        $this->loadSenders(); 
    }

    public function toggleAi()
    {
        if ($this->selectedSender) {
            $session = OrderSession::firstOrCreate(
                ['client_id' => $this->clientId, 'sender_id' => $this->selectedSender],
                ['is_human_agent_active' => false]
            );
            
            $session->is_human_agent_active = !$session->is_human_agent_active;
            $session->save();
            
            $this->isAiActive = !$session->is_human_agent_active;
        }
    }

    public function sendMessage()
    {
        $message = trim($this->newMessage);
        
        if (empty($message) || !$this->selectedSender || !$this->clientId) {
            return;
        }

        $client = Client::find($this->clientId);

        if (!$client || empty($client->fb_page_token)) {
            return;
        }

        $responseService = app(MessengerResponseService::class);
        $responseService->sendMessengerMessage($this->selectedSender, $message, $client->fb_page_token);

        // UI-এর জন্য ডাটাবেসে সেভ
        $responseService->logConversation($this->clientId, $this->selectedSender, null, $message, null);

        // 🔥 FIX 2: এআই-এর মেমোরি (OrderSession History)-তে অ্যাডমিনের মেসেজ যুক্ত করা
        $session = OrderSession::where('client_id', $this->clientId)->where('sender_id', $this->selectedSender)->first();
        if ($session) {
            $customerInfo = $session->customer_info ?? [];
            $history = $customerInfo['history'] ?? [];
            
            // অ্যাডমিনের মেসেজকে AI এর মেসেজ হিসেবে ট্যাগ করে দিচ্ছি, যাতে AI বুঝতে পারে তার তরফ থেকে কী বলা হয়েছে
            $history[] = ['user' => null, 'ai' => "[Human Admin Reply]: " . $message, 'time' => time()];
            
            $customerInfo['history'] = array_slice($history, -50);
            $session->update(['customer_info' => $customerInfo]);
        }

        $this->newMessage = '';
        $this->loadChat();
    }
}