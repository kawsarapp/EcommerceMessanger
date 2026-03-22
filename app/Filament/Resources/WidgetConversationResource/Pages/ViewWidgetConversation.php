<?php
namespace App\Filament\Resources\WidgetConversationResource\Pages;

use App\Filament\Resources\WidgetConversationResource;
use App\Models\OrderSession;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;

class ViewWidgetConversation extends Page
{
    protected static string $resource = WidgetConversationResource::class;
    protected static string $view = 'filament.pages.widget-conversation-view';

    public OrderSession $record;
    public string $replyText = '';

    public function mount(OrderSession $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        $name = $this->record->customer_info['name'] ?? 'Anonymous';
        return "💬 Chat — {$name}";
    }

    public function getHistory(): array
    {
        return $this->record->customer_info['history'] ?? [];
    }

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle_agent')
                ->label($this->record->is_human_agent_active ? '🤖 Hand to AI' : '👤 Take Over (Human)')
                ->color($this->record->is_human_agent_active ? 'gray' : 'warning')
                ->action(function () {
                    $this->record->update(['is_human_agent_active' => !$this->record->is_human_agent_active]);
                    $this->record->refresh();
                    Notification::make()
                        ->success()
                        ->title($this->record->is_human_agent_active ? 'Human agent active' : 'AI resumed')
                        ->send();
                }),

            Actions\Action::make('back')
                ->label('← All Conversations')
                ->color('gray')
                ->url(WidgetConversationResource::getUrl()),
        ];
    }

    public function sendReply(): void
    {
        $text = trim($this->replyText);
        if (empty($text)) return;

        $info    = $this->record->customer_info ?? [];
        $history = $info['history'] ?? [];

        $history[] = [
            'user'   => null,
            'ai'     => $text,
            'role'   => 'seller',   // marker so widget knows it's human reply
            'time'   => time(),
        ];

        $info['history'] = $history;

        $this->record->update([
            'customer_info'     => $info,
            'last_interacted_at'=> now(),
        ]);

        $this->replyText = '';
        $this->record->refresh();

        Log::info("Seller reply sent to widget conversation #{$this->record->id}");

        Notification::make()->success()->title('Reply sent ✅')->send();
    }
}
