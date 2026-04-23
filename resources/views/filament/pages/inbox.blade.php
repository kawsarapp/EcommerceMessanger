<x-filament-panels::page>

    {{-- ======================== LIGHTBOX MODAL ======================== --}}
    <div id="lightbox" onclick="this.classList.add('hidden')"
         class="hidden fixed inset-0 bg-black/90 z-[9999] flex items-center justify-center cursor-zoom-out p-4 backdrop-blur-sm">
        <img id="lightbox-img" src="" class="max-h-[90vh] max-w-[90vw] rounded-xl shadow-2xl object-contain"  loading="lazy" />
        <button onclick="document.getElementById('lightbox').classList.add('hidden')"
                class="absolute top-4 right-4 text-white bg-black/50 rounded-full p-2 hover:bg-black/70 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="flex flex-col md:flex-row bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden w-full"
         style="height: calc(100vh - 9rem);"
         wire:poll.3s="loadChat">

        {{-- ======================== LEFT SIDEBAR - Chat List ======================== --}}
        <div class="w-full md:w-1/3 lg:w-1/4 {{ $selectedSender ? 'hidden md:flex' : 'flex' }} flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20 overflow-hidden"
             style="height: 100%;">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex justify-between items-center shadow-sm shrink-0">
                <h3 class="text-base font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-inbox-stack class="w-5 h-5 text-primary-500" />
                    Unified Live Inbox
                </h3>
                <span class="bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 text-xs px-2.5 py-1 rounded-full font-bold">
                    {{ count($senders) }} Chats
                </span>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar">
                @forelse($senders as $sender)
                    @php
                        $displayName = $sender->sender_id;
                        if(($sender->platform ?? 'messenger') === 'whatsapp') {
                            $cleanNumber = explode('@', $sender->sender_id)[0];
                            $displayName = "+" . $cleanNumber;
                            if(isset($sender->metadata['sender_name']) && $sender->metadata['sender_name'] !== 'Customer') {
                                $displayName = $sender->metadata['sender_name'] . ' (' . $cleanNumber . ')';
                            }
                        } else {
                            $displayName = "Guest_" . substr($sender->sender_id, -4);
                        }
                    @endphp

                    <div wire:click="selectSender('{{ $sender->sender_id }}')"
                         class="p-3 border-b border-gray-100 dark:border-gray-800/60 cursor-pointer transition hover:bg-primary-50 dark:hover:bg-primary-900/20 group relative {{ $selectedSender === $sender->sender_id ? 'bg-primary-50 dark:bg-primary-900/30' : '' }}">
                        @if($selectedSender === $sender->sender_id)
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500 rounded-r-md"></div>
                        @endif
                        <div class="flex justify-between items-start mb-1 gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                @if(($sender->platform ?? 'messenger') === 'whatsapp')
                                    <div class="w-8 h-8 rounded-full text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0" style="background: linear-gradient(to top right, #22c55e, #4ade80);">
                                        <x-heroicon-s-phone class="w-4 h-4"/>
                                    </div>
                                @else
                                    <div class="w-8 h-8 rounded-full text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0" style="background: linear-gradient(to top right, #3b82f6, #60a5fa);">
                                        <x-heroicon-s-chat-bubble-oval-left class="w-4 h-4"/>
                                    </div>
                                @endif
                                <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $displayName }}</span>
                            </div>
                            <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ $sender->created_at->diffForHumans(null, true, true) }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate pl-10">
                            {{ $sender->user_message ?? ($sender->attachment_url ? '📎 File' : 'Attachment') }}
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

        {{-- ======================== RIGHT - Chat Area ======================== --}}
        <div class="w-full md:w-2/3 lg:w-3/4 {{ $selectedSender ? 'flex' : 'hidden md:flex' }} flex-col bg-[#F9FAFB] dark:bg-[#111827] overflow-hidden"
             style="height: 100%;">

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

                {{-- Header --}}
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur flex justify-between items-center shadow-sm z-20 shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <button wire:click="$set('selectedSender', null)" class="md:hidden mr-1 p-2 text-gray-500 bg-gray-100 dark:bg-gray-800 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-md flex-shrink-0" style="background: {{ $platformType === 'whatsapp' ? 'linear-gradient(to top right, #16a34a, #22c55e)' : 'linear-gradient(to top right, #2563eb, #3b82f6)' }};">
                            <x-heroicon-s-user class="w-5 h-5"/>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-sm text-gray-800 dark:text-gray-100 truncate">{{ $headerName }}</h3>
                            <p class="text-xs text-gray-500 flex items-center mt-0.5">
                                <span class="relative flex h-2 w-2 mr-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $isAiActive ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 {{ $isAiActive ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                </span>
                                {{ $isAiActive ? 'AI Auto Reply ON' : 'Human Mode Active' }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="toggleAi"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $isAiActive ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }} shadow-inner flex-shrink-0">
                        <span class="text-[9px] font-bold px-1 text-white absolute left-1 {{ $isAiActive ? 'opacity-0' : 'opacity-100' }}">OFF</span>
                        <span class="text-[9px] font-bold px-1 text-white absolute right-1 {{ $isAiActive ? 'opacity-100' : 'opacity-0' }}">ON</span>
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform {{ $isAiActive ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </div>

                {{-- Messages --}}
                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar" id="chat-container"
                     style="background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 24px 24px;">
                    <div class="space-y-4 pb-2" id="chat-messages">
                        @foreach($chatHistory as $chat)
                            @php
                                $isImage = $isVideo = $isAudio = $isPdf = false;
                                if($chat->attachment_url) {
                                    $ext = strtolower(pathinfo(parse_url($chat->attachment_url, PHP_URL_PATH), PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
                                    $isVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi']);
                                    $isAudio = in_array($ext, ['mp3', 'wav', 'ogg', 'oga', 'aac', 'opus', 'm4a']);
                                    $isPdf   = $ext === 'pdf';
                                }
                            @endphp

                            {{-- Customer Message --}}
                            @if($chat->user_message || ($chat->attachment_url && $chat->user_message !== null))
                                <div class="flex justify-start items-end gap-2 group">
                                    <div class="w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center mb-1 shadow-sm">
                                        <x-heroicon-s-user class="w-4 h-4 text-gray-500"/>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2.5 rounded-2xl rounded-bl-sm max-w-[80%] shadow-sm">
                                        @if($chat->user_message)
                                            <p class="text-sm whitespace-pre-wrap leading-relaxed">{{ $chat->user_message }}</p>
                                        @endif
                                        @if($chat->attachment_url)
                                            <div class="{{ $chat->user_message ? 'mt-2' : '' }}">
                                                @if($isImage)
                                                    <img src="{{ $chat->attachment_url }}" loading="lazy"
                                                         onclick="openLightbox('{{ $chat->attachment_url }}')"
                                                         class="max-w-[220px] rounded-lg border dark:border-gray-700 shadow-sm cursor-zoom-in hover:opacity-90 transition"
                                                         alt="Image" />
                                                @elseif($isVideo)
                                                    <video src="{{ $chat->attachment_url }}" controls class="max-w-[220px] rounded-lg shadow-sm"></video>
                                                @elseif($isAudio)
                                                    <audio src="{{ $chat->attachment_url }}" controls class="w-full max-w-[220px]"></audio>
                                                @elseif($isPdf)
                                                    <a href="{{ $chat->attachment_url }}" target="_blank"
                                                       class="flex items-center gap-2 p-2.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg border border-red-100 dark:border-red-800/30 hover:bg-red-100 transition">
                                                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2H5a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V9l-6-7zm1 1.5L14.5 9H10V3.5z"/></svg>
                                                        <span class="text-xs font-semibold">View PDF</span>
                                                    </a>
                                                @else
                                                    <a href="{{ $chat->attachment_url }}" target="_blank"
                                                       class="flex items-center gap-2 p-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg border border-blue-100 dark:border-blue-800/30 hover:bg-blue-100 transition">
                                                        <x-heroicon-o-paper-clip class="w-4 h-4"/>
                                                        <span class="text-xs font-semibold">Download File</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                        <span class="text-[10px] text-gray-400 mt-1 block opacity-60 group-hover:opacity-100 transition">{{ $chat->created_at->format('h:i A') }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Bot / Admin Message --}}
                            @if($chat->bot_response || ($chat->attachment_url && is_null($chat->user_message)))
                                <div class="flex justify-end items-end gap-2 group">
                                    <div class="text-white px-4 py-2.5 rounded-2xl rounded-br-sm max-w-[80%] shadow-md" style="background: {{ is_null($chat->user_message) ? 'linear-gradient(to bottom right, #4f46e5, #6366f1)' : 'linear-gradient(to bottom right, #0ea5e9, #0284c7)' }};">
                                        @if($chat->bot_response)
                                            <p class="text-sm whitespace-pre-wrap leading-relaxed">{{ $chat->bot_response }}</p>
                                        @endif
                                        @if($chat->attachment_url && is_null($chat->user_message))
                                            <div class="{{ $chat->bot_response ? 'mt-2' : '' }}">
                                                @if($isImage)
                                                    <img src="{{ $chat->attachment_url }}" loading="lazy"
                                                         onclick="openLightbox('{{ $chat->attachment_url }}')"
                                                         class="max-w-[220px] rounded-lg shadow-sm border border-white/20 cursor-zoom-in hover:opacity-90 transition"
                                                         alt="Image" />
                                                @elseif($isVideo)
                                                    <video src="{{ $chat->attachment_url }}" controls class="max-w-[220px] rounded-lg shadow-sm border border-white/20"></video>
                                                @elseif($isAudio)
                                                    <audio src="{{ $chat->attachment_url }}" controls class="w-full max-w-[220px]"></audio>
                                                @elseif($isPdf)
                                                    <a href="{{ $chat->attachment_url }}" target="_blank"
                                                       class="flex items-center gap-2 p-2.5 bg-white/20 hover:bg-white/30 text-white rounded-lg transition">
                                                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2H5a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V9l-6-7zm1 1.5L14.5 9H10V3.5z"/></svg>
                                                        <span class="text-xs font-semibold">View PDF</span>
                                                    </a>
                                                @else
                                                    <a href="{{ $chat->attachment_url }}" target="_blank"
                                                       class="flex items-center gap-2 p-2.5 bg-white/20 hover:bg-white/30 text-white rounded-lg transition">
                                                        <x-heroicon-o-paper-clip class="w-4 h-4"/>
                                                        <span class="text-xs font-semibold">View File</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="flex items-center justify-end mt-1.5 space-x-1 opacity-80 group-hover:opacity-100 transition">
                                            @if(is_null($chat->user_message))
                                                <x-heroicon-s-shield-check class="w-3 h-3 text-indigo-200"/>
                                                <span class="text-[9px] text-indigo-100 font-bold uppercase">{{ $chat->created_at->format('h:i A') }} • You</span>
                                            @else
                                                <x-heroicon-s-sparkles class="w-3 h-3 text-primary-200"/>
                                                <span class="text-[9px] text-primary-100 font-bold uppercase">{{ $chat->created_at->format('h:i A') }} • AI</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="w-7 h-7 rounded-full {{ is_null($chat->user_message) ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600' : 'bg-primary-100 dark:bg-primary-900/50 text-primary-600' }} flex-shrink-0 flex items-center justify-center mb-1 shadow-sm ring-2 ring-white dark:ring-gray-900">
                                        @if(is_null($chat->user_message))
                                            <x-heroicon-s-shield-check class="w-3.5 h-3.5"/>
                                        @else
                                            <x-heroicon-s-cpu-chip class="w-3.5 h-3.5"/>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- ======================== SEND BAR ======================== --}}
                <div class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shrink-0 z-20 rounded-br-2xl">

                    @if($attachment)
                        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between border-b border-gray-100 dark:border-gray-800">
                            <span class="text-sm text-gray-600 dark:text-gray-300 flex items-center gap-2">
                                <x-heroicon-o-paper-clip class="w-4 h-4 text-primary-500"/>
                                {{ $attachment->getClientOriginalName() }}
                            </span>
                            <button wire:click="$set('attachment', null)" type="button" class="text-red-500 bg-red-50 dark:bg-red-900/20 p-1 rounded-full hover:bg-red-100 transition">
                                <x-heroicon-o-x-mark class="w-4 h-4"/>
                            </button>
                        </div>
                    @endif

                    <div class="p-3">
                        <form wire:submit.prevent="sendMessage" class="flex items-center gap-2">

                            {{-- File Upload --}}
                            <label class="cursor-pointer p-2.5 text-gray-500 hover:text-primary-600 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 rounded-full transition flex-shrink-0" title="Attach file (Image, PDF, Doc, Audio, Video)">
                                <x-heroicon-o-paper-clip class="w-5 h-5"/>
                                <input type="file" wire:model="attachment" class="hidden"
                                       accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt">
                            </label>

                            {{-- Text Input --}}
                            <div class="flex-1 flex items-center bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-transparent transition pl-4 pr-1.5 py-1">
                                <input
                                    type="text"
                                    wire:model.defer="newMessage"
                                    wire:keydown.enter.prevent="sendMessage"
                                    placeholder="Type a message..."
                                    id="message-input"
                                    class="w-full bg-transparent border-none text-sm text-gray-800 dark:text-gray-100 focus:ring-0 outline-none placeholder-gray-400 py-1.5"
                                    autocomplete="off"
                                >
                                <button type="submit"
                                        class="p-2.5 bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-700 transition rounded-full text-white flex items-center justify-center shadow-md disabled:opacity-50 flex-shrink-0 ml-2"
                                        wire:loading.attr="disabled"
                                        wire:target="sendMessage">
                                    <span wire:loading wire:target="sendMessage">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </span>
                                    <span wire:loading.remove wire:target="sendMessage">
                                        <x-heroicon-s-paper-airplane class="w-4 h-4 transform -rotate-45"/>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-400 p-8 h-full">
                    <div class="w-28 h-28 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center mb-6 shadow-xl border border-gray-100 dark:border-gray-700 relative">
                        <div class="absolute inset-0 rounded-full animate-pulse opacity-10 bg-primary-400"></div>
                        <x-heroicon-o-chat-bubble-left-ellipsis class="w-14 h-14 text-primary-300 dark:text-primary-600"/>
                    </div>
                    <h2 class="text-xl font-bold text-gray-700 dark:text-gray-200 mb-2">Unified Inbox Center</h2>
                    <p class="text-sm text-center max-w-xs text-gray-500 leading-relaxed">
                        Select a chat from the sidebar to view the conversation and reply via Messenger or WhatsApp.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(156,163,175,0.4); border-radius: 20px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(75,85,99,0.5); }
    </style>

    <script>
        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox').classList.remove('hidden');
        }

        function scrollChatToBottom(smooth = true) {
            const container = document.getElementById('chat-container');
            if (container) {
                container.scrollTo({ top: container.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
            }
        }

        // Scroll immediately on page load
        document.addEventListener('DOMContentLoaded', () => scrollChatToBottom(false));

        // Scroll after every Livewire update
        document.addEventListener('livewire:update', () => {
            setTimeout(() => scrollChatToBottom(true), 50);
        });

        // Re-focus input after sending
        document.addEventListener('livewire:update', () => {
            const input = document.getElementById('message-input');
            if (input) setTimeout(() => input.focus(), 100);
        });
    </script>
</x-filament-panels::page>