<x-filament-panels::page>
    <div class="flex flex-col md:flex-row h-[calc(100vh-10rem)] md:h-[calc(100vh-8rem)] lg:h-[85vh] bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden w-full" wire:poll.3s="loadChat">

        <div class="w-full md:w-1/3 lg:w-1/4 {{ $selectedSender ? 'hidden md:flex' : 'flex' }} flex-col h-full border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20 transition-all overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex justify-between items-center shadow-sm shrink-0">
                <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-inbox-stack class="w-5 h-5 text-primary-500" />
                    Unified Live Inbox
                </h3>
                <span class="bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 text-[10px] md:text-xs px-2.5 py-1 rounded-full font-bold shadow-inner">
                    {{ count($senders) }} Chats
                </span>
            </div>

            <div class="flex-1 overflow-y-auto scroll-smooth custom-scrollbar">
                @forelse($senders as $sender)
                    @php
                        // 🔥 নাম্বার ফরম্যাটিং ম্যাজিক (Full Number Show করার জন্য)
                        $displayName = $sender->sender_id;
                        if(($sender->platform ?? 'messenger') === 'whatsapp') {
                            $cleanNumber = explode('@', $sender->sender_id)[0]; // @c.us বা @lid কেটে ফেলা
                            $displayName = "+" . $cleanNumber; // সামনে + যুক্ত করা
                            
                            // যদি কাস্টমারের নাম সেভ থাকে, তবে নামের সাথে নম্বর দেখাবে
                            if(isset($sender->metadata['sender_name']) && $sender->metadata['sender_name'] !== 'Customer') {
                                $displayName = $sender->metadata['sender_name'] . ' (' . $cleanNumber . ')';
                            }
                        } else {
                            $displayName = "Guest_" . substr($sender->sender_id, -4);
                        }
                    @endphp

                    <div 
                        wire:click="selectSender('{{ $sender->sender_id }}')"
                        class="p-3 md:p-4 border-b border-gray-100 dark:border-gray-800/60 cursor-pointer transition duration-200 ease-in-out hover:bg-primary-50 dark:hover:bg-primary-900/20 group relative {{ $selectedSender === $sender->sender_id ? 'bg-primary-50 dark:bg-primary-900/30' : '' }}"
                    >
                        @if($selectedSender === $sender->sender_id)
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500 rounded-r-md shadow-[2px_0_8px_rgba(var(--primary-500),0.5)]"></div>
                        @endif
                        
                        <div class="flex justify-between items-start mb-1 gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                @if(($sender->platform ?? 'messenger') === 'whatsapp')
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-green-500 to-green-400 text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0" title="WhatsApp">
                                        <x-heroicon-s-phone class="w-4 h-4"/>
                                    </div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $displayName }}</span>
                                @else
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-blue-400 text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0" title="Messenger">
                                        <x-heroicon-s-chat-bubble-oval-left class="w-4 h-4"/>
                                    </div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $displayName }}</span>
                                @endif
                            </div>
                            <span class="text-[10px] text-gray-400 whitespace-nowrap font-medium">{{ $sender->created_at->diffForHumans(null, true, true) }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate pl-10">
                            {{ $sender->user_message ?? ($sender->attachment_url ? '📎 Media File' : 'Attachment Received') }}
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

        <div class="w-full md:w-2/3 lg:w-3/4 {{ $selectedSender ? 'flex' : 'hidden md:flex' }} flex-col relative bg-[#F9FAFB] dark:bg-[#111827] h-full overflow-hidden">
            
            @if($selectedSender)
                @php
                    $headerName = $selectedSender;
                    $platformType = 'messenger';
                    $latest = collect($senders)->where('sender_id', $selectedSender)->first();
                    if($latest && ($latest->platform ?? 'messenger') === 'whatsapp') {
                        $platformType = 'whatsapp';
                        $clean = explode('@', $selectedSender)[0];
                        $headerName = "+" . $clean;
                        if(isset($latest->metadata['sender_name']) && $latest->metadata['sender_name'] !== 'Customer') {
                            $headerName = $latest->metadata['sender_name'] . ' (' . $clean . ')';
                        }
                    }
                @endphp

                <div class="px-3 md:px-6 py-3 border-b border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md flex justify-between items-center shadow-sm z-20 shrink-0">
                    <div class="flex items-center gap-2 md:gap-3 min-w-0">
                        <button wire:click="$set('selectedSender', null)" class="md:hidden mr-1 p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-800 rounded-full transition-colors flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <div class="w-9 h-9 md:w-11 md:h-11 rounded-full {{ $platformType === 'whatsapp' ? 'bg-gradient-to-tr from-green-600 to-green-500' : 'bg-gradient-to-tr from-blue-600 to-blue-500' }} flex items-center justify-center text-white font-bold shadow-md flex-shrink-0">
                            <x-heroicon-s-user class="w-5 h-5 md:w-6 md:h-6"/>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-sm md:text-base text-gray-800 dark:text-gray-100 flex items-center gap-2 truncate">
                                <span class="truncate">{{ $headerName }}</span>
                            </h3>
                            <p class="text-[10px] md:text-xs text-gray-500 flex items-center mt-0.5 font-medium">
                                <span class="relative flex h-2 w-2 mr-1.5">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $isAiActive ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 {{ $isAiActive ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                </span>
                                {{ $isAiActive ? 'AI Auto Reply ON' : 'Human Mode' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800 px-2 md:px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-700 shrink-0">
                        <span class="text-[9px] md:text-xs font-bold uppercase tracking-wider {{ $isAiActive ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }} hidden sm:block">
                            AI {{ $isAiActive ? 'ON' : 'OFF' }}
                        </span>
                        <button 
                            wire:click="toggleAi" 
                            class="relative inline-flex h-5 w-10 md:h-6 md:w-11 items-center rounded-full transition-colors duration-300 focus:outline-none {{ $isAiActive ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }} shadow-inner flex-shrink-0"
                            title="Toggle AI"
                        >
                            <span class="inline-block h-3.5 w-3.5 md:h-4.5 md:w-4.5 transform rounded-full bg-white shadow-sm transition-transform duration-300 {{ $isAiActive ? 'translate-x-5 md:translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 md:p-6 flex flex-col-reverse custom-scrollbar" id="chat-container" style="background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 24px 24px;">
                    <div class="space-y-4 md:space-y-6 pb-4">
                        @foreach($chatHistory as $chat)
                            
                            @php
                                $isImage = $isVideo = $isAudio = false;
                                if($chat->attachment_url) {
                                    $ext = strtolower(pathinfo(parse_url($chat->attachment_url, PHP_URL_PATH), PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    $isVideo = in_array($ext, ['mp4', 'webm', 'ogg_video']);
                                    $isAudio = in_array($ext, ['mp3', 'wav', 'ogg', 'oga', 'aac', 'opus']);
                                }
                            @endphp

                            @if($chat->user_message || $chat->attachment_url)
                                <div class="flex justify-start items-end gap-2 group">
                                    <div class="w-6 h-6 md:w-8 md:h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center mb-1 shadow-sm">
                                        <x-heroicon-s-user class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 px-3 py-2 md:px-4 md:py-3 rounded-2xl rounded-bl-sm max-w-[90%] md:max-w-[75%] shadow-sm hover:shadow-md transition-shadow">
                                        @if($chat->user_message)
                                            <p class="text-sm md:text-[15px] whitespace-pre-wrap leading-relaxed">{{ $chat->user_message }}</p>
                                        @endif
                                        
                                        @if($chat->attachment_url)
                                            <div class="{{ $chat->user_message ? 'mt-3' : '' }}">
                                                @if($isImage)
                                                    <a href="{{ $chat->attachment_url }}" target="_blank"><img src="{{ $chat->attachment_url }}" class="max-w-[200px] md:max-w-xs rounded-lg border dark:border-gray-700 shadow-sm" alt="Attached Image" /></a>
                                                @elseif($isVideo)
                                                    <video src="{{ $chat->attachment_url }}" controls class="max-w-[200px] md:max-w-xs rounded-lg shadow-sm"></video>
                                                @elseif($isAudio)
                                                    <audio src="{{ $chat->attachment_url }}" controls class="w-full max-w-[200px] md:max-w-xs"></audio>
                                                @else
                                                    <a href="{{ $chat->attachment_url }}" target="_blank" class="flex items-center gap-2 p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg border border-blue-100 dark:border-blue-800/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                                                        <x-heroicon-o-paper-clip class="w-4 h-4"/>
                                                        <span class="text-[10px] md:text-sm font-medium uppercase">Download File</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                        <span class="text-[8px] md:text-[10px] text-gray-400 mt-1 block opacity-60 group-hover:opacity-100 transition-opacity">{{ $chat->created_at->format('h:i A') }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($chat->bot_response || ($chat->attachment_url && !$chat->user_message))
                                <div class="flex justify-end items-end gap-2 group">
                                    <div class="{{ is_null($chat->user_message) ? 'bg-gradient-to-br from-indigo-600 to-indigo-500' : 'bg-gradient-to-br from-primary-600 to-primary-500' }} text-white px-3 py-2 md:px-4 md:py-3 rounded-2xl rounded-br-sm max-w-[90%] md:max-w-[75%] shadow-md hover:shadow-lg transition-shadow">
                                        @if($chat->bot_response)
                                            <p class="text-sm md:text-[15px] whitespace-pre-wrap leading-relaxed">{{ $chat->bot_response }}</p>
                                        @endif

                                        @if($chat->attachment_url && !$chat->user_message)
                                            <div class="{{ $chat->bot_response ? 'mt-3' : '' }}">
                                                @if($isImage)
                                                    <a href="{{ $chat->attachment_url }}" target="_blank"><img src="{{ $chat->attachment_url }}" class="max-w-[200px] rounded-lg shadow-sm border border-white/20" alt="Image" /></a>
                                                @elseif($isVideo)
                                                    <video src="{{ $chat->attachment_url }}" controls class="max-w-[200px] rounded-lg shadow-sm border border-white/20"></video>
                                                @elseif($isAudio)
                                                    <audio src="{{ $chat->attachment_url }}" controls class="w-full max-w-[200px]"></audio>
                                                @else
                                                    <a href="{{ $chat->attachment_url }}" target="_blank" class="flex items-center gap-2 p-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors">
                                                        <x-heroicon-o-paper-clip class="w-4 h-4"/>
                                                        <span class="text-[10px] md:text-sm font-medium uppercase">View File</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        <div class="flex items-center justify-end mt-1.5 space-x-1 opacity-80 group-hover:opacity-100 transition-opacity">
                                            @if(is_null($chat->user_message))
                                                <x-heroicon-s-check-badge class="w-3 h-3 text-indigo-200"/>
                                                <span class="text-[8px] md:text-[10px] text-indigo-100 font-bold tracking-wide uppercase">{{ $chat->created_at->format('h:i A') }} • You</span>
                                            @else
                                                <x-heroicon-s-sparkles class="w-3 h-3 text-primary-200"/>
                                                <span class="text-[8px] md:text-[10px] text-primary-100 font-bold tracking-wide uppercase">{{ $chat->created_at->format('h:i A') }} • AI</span>
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

                <div class="flex flex-col border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shrink-0 shadow-lg z-20 w-full rounded-br-2xl">
                    
                    @if($attachment)
                        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between border-b border-gray-100 dark:border-gray-800">
                            <span class="text-sm text-gray-600 dark:text-gray-300 font-medium flex items-center gap-2">
                                <x-heroicon-o-paper-clip class="w-4 h-4 text-primary-500"/>
                                {{ $attachment->getClientOriginalName() }}
                            </span>
                            <button wire:click="$set('attachment', null)" type="button" class="text-red-500 bg-red-50 dark:bg-red-900/20 p-1 rounded-full hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                                <x-heroicon-o-x-mark class="w-4 h-4"/>
                            </button>
                        </div>
                    @endif

                    <div class="p-3 md:p-4 w-full">
                        <form wire:submit.prevent="sendMessage" class="flex items-center gap-2 max-w-5xl mx-auto w-full relative">
                            
                            <label class="cursor-pointer p-2.5 text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:bg-gray-700 rounded-full transition-colors flex-shrink-0 shadow-inner">
                                <x-heroicon-o-paper-clip class="w-5 h-5"/>
                                <input type="file" wire:model="attachment" class="hidden" accept="image/*,video/*,audio/*,.pdf,.doc,.docx">
                            </label>

                            <div class="flex-1 flex items-center bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-transparent transition-all shadow-inner pl-4 pr-1.5 py-1.5">
                                <input 
                                    type="text" 
                                    wire:model="newMessage"
                                    placeholder="Type a message or attach a file..." 
                                    class="w-full bg-transparent border-none text-sm md:text-base text-gray-800 dark:text-gray-100 focus:ring-0 outline-none placeholder-gray-400 py-2"
                                    autocomplete="off"
                                >
                                
                                <button 
                                    type="submit" 
                                    class="p-2.5 bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-700 hover:to-primary-600 transition-all rounded-full text-white flex items-center justify-center shadow-md disabled:opacity-50 group flex-shrink-0 ml-2"
                                    wire:loading.attr="disabled"
                                >
                                    <x-heroicon-s-paper-airplane class="w-5 h-5 transform -rotate-45 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"/>
                                </button>
                            </div>
                        </form>
                        <div class="hidden sm:flex justify-center items-center gap-1.5 mt-2">
                            <span class="text-[9px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-tighter italic">Human Mode Active - AI will pause on send</span>
                        </div>
                    </div>
                </div>

            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-400 p-8 bg-gradient-to-b from-transparent to-gray-50 dark:to-gray-900/30 h-full">
                    <div class="w-20 h-20 md:w-32 md:h-32 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center mb-6 shadow-xl border border-gray-100 dark:border-gray-700 relative">
                        <div class="absolute inset-0 rounded-full animate-pulse opacity-10 bg-primary-400"></div>
                        <x-heroicon-o-chat-bubble-left-ellipsis class="w-10 h-10 md:w-16 md:h-16 text-primary-300 dark:text-primary-600"/>
                    </div>
                    <h2 class="text-lg md:text-2xl font-bold text-gray-700 dark:text-gray-200 mb-2 text-center">Unified Inbox Center</h2>
                    <p class="text-xs md:text-base text-center max-w-xs md:max-w-md text-gray-500 leading-relaxed">
                        Select a chat from the sidebar to view history, monitor AI performance, reply via Messenger/WhatsApp or upload attachments.
                    </p>
                </div>
            @endif
            
        </div>
    </div>
    
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(156, 163, 175, 0.3); border-radius: 20px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(75, 85, 99, 0.4); }
        @media (min-width: 768px) {
            .custom-scrollbar::-webkit-scrollbar-thumb { visibility: hidden; }
            .custom-scrollbar:hover::-webkit-scrollbar-thumb { visibility: visible; }
        }
    </style>

    <script>
        document.addEventListener('livewire:load', function () {
            const scrollToBottom = () => {
                const container = document.getElementById('chat-container');
                if(container) {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            };
            scrollToBottom();
            Livewire.hook('message.processed', (message, component) => {
                if (component.fingerprint.name === 'live-chat-component' || document.getElementById('chat-container')) {
                    scrollToBottom();
                }
            });
        });
    </script>
</x-filament-panels::page>