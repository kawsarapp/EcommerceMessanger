<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Conversation;
use App\Models\OrderSession;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use App\Services\Messenger\MessengerResponseService;
use Livewire\WithFileUploads;

class Inbox extends Page
{
    use WithFileUploads; // 🔥 ফাইল আপলোডের জন্য যুক্ত করা হলো

    protected static bool $shouldRegisterNavigation = true;
    protected static ?int $navigationSort = 1; 
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Customer Support';
    protected static ?string $navigationLabel = 'Live Inbox';
    protected static ?string $title = 'Unified Inbox (Messenger & WhatsApp)';

    protected static string $view = 'filament.pages.inbox';

    public $clientId;
    public $senders = [];
    public $selectedSender = null;
    public $chatHistory = [];
    public $isAiActive = true;
    public $newMessage = ''; 
    public $attachment; // 🔥 এটা দিয়ে ড্যাশবোর্ড থেকে ফাইল আপলোড হবে

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
        
        // যদি টেক্সট বা ফাইল কোনোটিই না থাকে, তবে কিছুই করবে না
        if (empty($message) && !$this->attachment) {
            return;
        }

        if (!$this->selectedSender || !$this->clientId) return;

        $client = Client::find($this->clientId);

        $latestConvo = Conversation::where('client_id', $this->clientId)
            ->where('sender_id', $this->selectedSender)
            ->latest('id')
            ->first();
            
        $platform = $latestConvo->platform ?? 'messenger';

        // 🌟 ফাইল হ্যান্ডলিং (আপলোড এবং Base64 কনভার্ট)
        $attachmentUrl = null;
        $mediaData = null;

        if ($this->attachment) {
            $path = $this->attachment->store('chat_attachments', 'public');
            $attachmentUrl = asset('storage/' . $path);
            
            // হোয়াটসঅ্যাপে পাঠানোর জন্য Base64 ফরম্যাট তৈরি
            if ($platform === 'whatsapp') {
                $mediaData = [
                    'mimetype' => $this->attachment->getMimeType(),
                    'data' => base64_encode(file_get_contents($this->attachment->getRealPath())),
                    'filename' => $this->attachment->getClientOriginalName()
                ];
            }
        }

        // 🚀 হোয়াটসঅ্যাপে পাঠানো
        if ($platform === 'whatsapp') {
            if ($client && $client->wa_instance_id) {
                \Illuminate\Support\Facades\Http::post(config('services.whatsapp.api_url') . '/api/send-message', [
                    'instance_id' => $client->wa_instance_id,
                    'to' => $this->selectedSender,
                    'message' => $message,
                    'media' => $mediaData // মিডিয়া পাঠানো হচ্ছে
                ]);

                Conversation::create([
                    'client_id' => $this->clientId,
                    'sender_id' => $this->selectedSender,
                    'platform' => 'whatsapp',
                    'bot_response' => empty($message) ? null : $message,
                    'attachment_url' => $attachmentUrl
                ]);
            }
        } 
        // 🚀 মেসেঞ্জারে পাঠানো
        else {
            if ($client && !empty($client->fb_page_token)) {
                $responseService = app(MessengerResponseService::class);
                $responseService->sendMessengerMessage($this->selectedSender, $message, $client->fb_page_token);
                $responseService->logConversation($this->clientId, $this->selectedSender, null, $message, $attachmentUrl);
            }
        }

        // মেমোরি আপডেট
        $session = OrderSession::where('client_id', $this->clientId)->where('sender_id', $this->selectedSender)->first();
        if ($session) {
            $customerInfo = $session->customer_info ?? [];
            $history = $customerInfo['history'] ?? [];
            $history[] = ['user' => null, 'ai' => "[Human Admin Reply]: " . $message, 'time' => time()];
            $customerInfo['history'] = array_slice($history, -50);
            $session->update(['customer_info' => $customerInfo]);
        }

        $this->newMessage = '';
        $this->attachment = null; // সেন্ড করার পর ফাইল মুছে ফেলবে
        $this->loadChat();
    }
}