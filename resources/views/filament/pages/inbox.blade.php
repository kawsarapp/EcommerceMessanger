<x-filament-panels::page>
    <div class="flex h-[75vh] bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden" wire:poll.3s="loadChat">
        
        <div class="w-1/3 border-r border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Customers</h3>
            </div>
            
            <div class="flex-1 overflow-y-auto">
                @forelse($senders as $sender)
                    <div 
                        wire:click="selectSender('{{ $sender->sender_id }}')"
                        class="p-4 border-b border-gray-100 dark:border-gray-800 cursor-pointer transition duration-150 ease-in-out hover:bg-primary-50 dark:hover:bg-primary-900/20 {{ $selectedSender === $sender->sender_id ? 'bg-primary-50 dark:bg-primary-900/30 border-l-4 border-l-primary-500' : '' }}"
                    >
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white">Guest ({{ substr($sender->sender_id, -6) }})</span>
                            <span class="text-xs text-gray-500">{{ $sender->created_at->diffForHumans(null, true, true) }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $sender->user_message ?? 'Admin replied / Attachment' }}
                        </p>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        <x-heroicon-o-chat-bubble-left-right class="w-12 h-12 mx-auto text-gray-300 mb-2"/>
                        No conversations yet.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="w-2/3 flex flex-col relative bg-[#F9FAFB] dark:bg-gray-900/50">
            
            @if($selectedSender)
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex justify-between items-center shadow-sm z-10">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-bold">
                            <x-heroicon-o-user class="w-6 h-6"/>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100">Customer ID: {{ $selectedSender }}</h3>
                            <p class="text-xs text-gray-500 flex items-center">
                                <span class="w-2 h-2 rounded-full mr-1 {{ $isAiActive ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                {{ $isAiActive ? 'AI Bot is managing this chat' : 'Human Agent Mode (AI Paused)' }}
                            </p>
                        </div>
                    </div>

                    <button 
                        wire:click="toggleAi" 
                        class="relative inline-flex h-8 w-16 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $isAiActive ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                    >
                        <span class="sr-only">Toggle AI Auto Reply</span>
                        <span class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform {{ $isAiActive ? 'translate-x-9' : 'translate-x-1' }}"></span>
                        <span class="absolute text-[10px] font-bold {{ $isAiActive ? 'left-2 text-white' : 'right-2 text-gray-600 dark:text-gray-300' }}">
                            {{ $isAiActive ? 'ON' : 'OFF' }}
                        </span>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-6 flex flex-col-reverse" id="chat-container">
                    <div class="space-y-6">
                        @foreach($chatHistory as $chat)
                            
                            @if($chat->user_message || $chat->attachment_url)
                                <div class="flex justify-start">
                                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 px-4 py-3 rounded-2xl rounded-tl-none max-w-[80%] shadow-sm">
                                        @if($chat->user_message)
                                            <p class="text-sm whitespace-pre-wrap">{{ $chat->user_message }}</p>
                                        @endif
                                        @if($chat->attachment_url)
                                            <span class="text-xs text-blue-500 mt-1 block">📎 Attachment Received</span>
                                        @endif
                                        <span class="text-[10px] text-gray-400 mt-2 block">{{ $chat->created_at->format('h:i A') }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($chat->bot_response)
                                <div class="flex justify-end">
                                    <div class="{{ is_null($chat->user_message) ? 'bg-indigo-600' : 'bg-primary-500' }} text-white px-4 py-3 rounded-2xl rounded-tr-none max-w-[80%] shadow-md">
                                        <p class="text-sm whitespace-pre-wrap">{{ $chat->bot_response }}</p>
                                        <div class="flex items-center justify-end mt-2 space-x-1">
                                            @if(is_null($chat->user_message))
                                                <x-heroicon-s-user class="w-3 h-3 text-indigo-200"/>
                                                <span class="text-[10px] text-indigo-100">{{ $chat->created_at->format('h:i A') }} • Admin Reply</span>
                                            @else
                                                <x-heroicon-s-sparkles class="w-3 h-3 text-primary-200"/>
                                                <span class="text-[10px] text-primary-100">{{ $chat->created_at->format('h:i A') }} • AI Reply</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                        @endforeach
                    </div>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
                    <form wire:submit.prevent="sendMessage" class="relative flex items-center">
                        <input 
                            type="text" 
                            wire:model="newMessage"
                            placeholder="Type a message..." 
                            class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-full px-4 py-3 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-primary-500 pr-12 outline-none"
                            autocomplete="off"
                        >
                        <button 
                            type="submit" 
                            class="absolute right-2 p-2 bg-primary-600 hover:bg-primary-700 transition rounded-full text-white flex items-center justify-center"
                            wire:loading.attr="disabled"
                        >
                            <x-heroicon-s-paper-airplane class="w-4 h-4"/>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-400">
                    <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4 shadow-inner">
                        <x-heroicon-o-chat-bubble-left-ellipsis class="w-12 h-12 text-gray-300 dark:text-gray-600"/>
                    </div>
                    <h2 class="text-xl font-bold text-gray-600 dark:text-gray-300">Welcome to Live Inbox</h2>
                    <p class="text-sm mt-2">Select a conversation from the left to start chatting.</p>
                </div>
            @endif
            
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.hook('message.processed', (message, component) => {
                let container = document.getElementById('chat-container');
                if(container) { container.scrollTop = container.scrollHeight; }
            });
        });
    </script>
</x-filament-panels::page>