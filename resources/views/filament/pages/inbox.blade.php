<x-filament-panels::page>
    <div class="flex flex-col md:flex-row h-[82vh] bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden" wire:poll.1s="loadChat">

        <div class="w-full md:w-1/3 lg:w-1/4 h-1/3 md:h-full border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20 flex flex-col transition-all">
            <div class="p-3 md:p-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 sticky top-0 z-10 flex justify-between items-center shadow-sm">
                <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-inbox-stack class="w-5 h-5 text-primary-500" />
                    Live Inbox
                </h3>
                <span class="bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 text-xs px-2.5 py-1 rounded-full font-bold shadow-inner">
                    {{ count($senders) }} Chats
                </span>
            </div>

            <div class="flex-1 overflow-y-auto scroll-smooth custom-scrollbar">
                @forelse($senders as $sender)
                    <div 
                        wire:click="selectSender('{{ $sender->sender_id }}')"
                        class="p-3 md:p-4 border-b border-gray-100 dark:border-gray-800/60 cursor-pointer transition duration-200 ease-in-out hover:bg-primary-50 dark:hover:bg-primary-900/20 group relative {{ $selectedSender === $sender->sender_id ? 'bg-primary-50 dark:bg-primary-900/30' : '' }}"
                    >
                        @if($selectedSender === $sender->sender_id)
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500 rounded-r-md shadow-[2px_0_8px_rgba(var(--primary-500),0.5)]"></div>
                        @endif
                        
                        <div class="flex justify-between items-start mb-1">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-primary-500 to-primary-400 text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0">
                                    <x-heroicon-s-user class="w-4 h-4"/>
                                </div>
                                <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">Guest_{{ substr($sender->sender_id, -4) }}</span>
                            </div>
                            <span class="text-[10px] md:text-xs text-gray-400 whitespace-nowrap ml-2 font-medium">{{ $sender->created_at->diffForHumans(null, true, true) }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate pl-10">
                            {{ $sender->user_message ?? '📎 Attachment Received' }}
                        </p>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-400 flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-chat-bubble-oval-left-ellipsis class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-3 opacity-50"/>
                        <p class="text-sm font-medium">No conversations yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="w-full md:w-2/3 lg:w-3/4 h-2/3 md:h-full flex flex-col relative bg-[#F9FAFB] dark:bg-[#111827]">
            
            @if($selectedSender)
                <div class="px-4 py-2.5 md:p-4 border-b border-gray-200 dark:border-gray-800 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md flex justify-between items-center shadow-sm z-10 sticky top-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-full bg-gradient-to-tr from-gray-700 to-gray-600 flex items-center justify-center text-white font-bold shadow-md">
                            <x-heroicon-s-user class="w-5 h-5 md:w-6 md:h-6"/>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm md:text-base text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                Customer {{ substr($selectedSender, -6) }}
                                <span class="hidden lg:inline-block text-xs font-normal text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full">ID: {{ $selectedSender }}</span>
                            </h3>
                            <p class="text-[10px] md:text-xs text-gray-500 flex items-center mt-0.5 font-medium">
                                <span class="relative flex h-2 w-2 mr-1.5">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $isAiActive ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 {{ $isAiActive ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                </span>
                                {{ $isAiActive ? 'AI Assistant is Active' : 'Human Mode (AI Paused)' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-700">
                        <span class="text-[10px] md:text-xs font-bold uppercase tracking-wider {{ $isAiActive ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }} hidden md:block">
                            {{ $isAiActive ? 'Auto Reply ON' : 'Auto Reply OFF' }}
                        </span>
                        <button 
                            wire:click="toggleAi" 
                            class="relative inline-flex h-6 w-11 md:h-7 md:w-12 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $isAiActive ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }} shadow-inner"
                            title="Toggle AI"
                        >
                            <span class="inline-block h-4 w-4 md:h-5 md:w-5 transform rounded-full bg-white shadow-sm transition-transform duration-300 {{ $isAiActive ? 'translate-x-6 md:translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-4 md:space-y-6 flex flex-col-reverse custom-scrollbar" id="chat-container" style="background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 24px 24px;">
                    <div class="space-y-4 md:space-y-6 pb-2">
                        @foreach($chatHistory as $chat)
                            
                            @if($chat->user_message || $chat->attachment_url)
                                <div class="flex justify-start items-end gap-2 group">
                                    <div class="w-6 h-6 md:w-8 md:h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center mb-1 shadow-sm">
                                        <x-heroicon-s-user class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 px-4 py-3 rounded-2xl rounded-bl-sm max-w-[85%] md:max-w-[75%] shadow-sm hover:shadow-md transition-shadow">
                                        @if($chat->user_message)
                                            <p class="text-sm md:text-[15px] whitespace-pre-wrap leading-relaxed">{{ $chat->user_message }}</p>
                                        @endif
                                        @if($chat->attachment_url)
                                            <a href="{{ $chat->attachment_url }}" target="_blank" class="flex items-center gap-2 mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg border border-blue-100 dark:border-blue-800/30 hover:bg-blue-100 transition-colors">
                                                <x-heroicon-o-paper-clip class="w-4 h-4"/>
                                                <span class="text-xs md:text-sm font-medium">View Attachment</span>
                                            </a>
                                        @endif
                                        <span class="text-[9px] md:text-[10px] text-gray-400 mt-1 block opacity-50 group-hover:opacity-100 transition-opacity">{{ $chat->created_at->format('h:i A') }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($chat->bot_response)
                                <div class="flex justify-end items-end gap-2 group">
                                    <div class="{{ is_null($chat->user_message) ? 'bg-gradient-to-br from-indigo-600 to-indigo-500' : 'bg-gradient-to-br from-primary-600 to-primary-500' }} text-white px-4 py-3 rounded-2xl rounded-br-sm max-w-[85%] md:max-w-[75%] shadow-md hover:shadow-lg transition-shadow">
                                        <p class="text-sm md:text-[15px] whitespace-pre-wrap leading-relaxed">{{ $chat->bot_response }}</p>
                                        <div class="flex items-center justify-end mt-1.5 space-x-1 opacity-80 group-hover:opacity-100 transition-opacity">
                                            @if(is_null($chat->user_message))
                                                <x-heroicon-s-check-badge class="w-3 h-3 text-indigo-200"/>
                                                <span class="text-[9px] md:text-[10px] text-indigo-100 font-bold tracking-wide uppercase">{{ $chat->created_at->format('h:i A') }} • You</span>
                                            @else
                                                <x-heroicon-s-sparkles class="w-3 h-3 text-primary-200"/>
                                                <span class="text-[9px] md:text-[10px] text-primary-100 font-bold tracking-wide uppercase">{{ $chat->created_at->format('h:i A') }} • AI Bot</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="w-6 h-6 md:w-8 md:h-8 rounded-full {{ is_null($chat->user_message) ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400' : 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' }} flex-shrink-0 flex items-center justify-center mb-1 shadow-sm ring-2 ring-white dark:ring-gray-900">
                                        @if(is_null($chat->user_message))
                                            <x-heroicon-s-shield-check class="w-4 h-4"/>
                                        @else
                                            <x-heroicon-s-cpu-chip class="w-4 h-4"/>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            
                        @endforeach
                    </div>
                </div>

                <div class="p-3 md:p-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-10">
                    <form wire:submit.prevent="sendMessage" class="relative flex items-center gap-2 max-w-5xl mx-auto">
                        <div class="relative flex-1 flex items-center bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-transparent transition-all shadow-inner pl-4 pr-2 py-1.5 md:py-2">
                            
                            <input 
                                type="text" 
                                wire:model="newMessage"
                                placeholder="Type a message to send directly to messenger..." 
                                class="w-full bg-transparent border-none text-sm md:text-base text-gray-800 dark:text-gray-100 focus:ring-0 outline-none placeholder-gray-400 py-2"
                                autocomplete="off"
                            >
                            
                            <button 
                                type="submit" 
                                class="p-2.5 bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-700 hover:to-primary-600 transition-all rounded-full text-white flex items-center justify-center shadow-md disabled:opacity-50 disabled:cursor-not-allowed group flex-shrink-0"
                                wire:loading.attr="disabled"
                            >
                                <x-heroicon-s-paper-airplane class="w-4 h-4 md:w-5 md:h-5 transform -rotate-45 group-hover:scale-110 transition-transform"/>
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-2 hidden md:block">
                        <span class="text-[10px] text-gray-400 font-medium"><span class="text-indigo-500">Tip:</span> This message will be sent as a Human Agent. The AI will pause automatically if you take over.</span>
                    </div>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-400 p-6 bg-gradient-to-b from-transparent to-gray-50 dark:to-gray-900/50">
                    <div class="w-24 h-24 md:w-32 md:h-32 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center mb-6 shadow-xl border border-gray-100 dark:border-gray-700 relative">
                        <div class="absolute inset-0 rounded-full animate-ping opacity-10 bg-primary-400"></div>
                        <x-heroicon-o-chat-bubble-left-ellipsis class="w-12 h-12 md:w-16 md:h-16 text-primary-300 dark:text-primary-600"/>
                    </div>
                    <h2 class="text-xl md:text-2xl font-bold text-gray-700 dark:text-gray-200 mb-2 text-center">Welcome to Control Center</h2>
                    <p class="text-sm md:text-base text-center max-w-md text-gray-500 leading-relaxed">Select a conversation from the sidebar to monitor AI responses or take over the chat manually.</p>
                </div>
            @endif
            
        </div>
    </div>
    
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(156, 163, 175, 0.4); border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: rgba(107, 114, 128, 0.7); }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(75, 85, 99, 0.5); }
        .dark .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: rgba(107, 114, 128, 0.8); }
    </style>

    <script>
        document.addEventListener('livewire:load', function () {
            const scrollToBottom = () => {
                let container = document.getElementById('chat-container');
                if(container) { container.scrollTop = container.scrollHeight; }
            };

            // স্ক্রল টু বটম প্রথমবার লোড হওয়ার সময়
            scrollToBottom();

            // লাইভওয়্যার মেসেজ আপডেট হলে আবার নিচে স্ক্রল করবে
            Livewire.hook('message.processed', (message, component) => {
                scrollToBottom();
            });
        });
    </script>
</x-filament-panels::page>