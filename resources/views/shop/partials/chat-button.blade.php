{{-- 
    Chat Button Partial - Shows WhatsApp/Messenger links
    Required variables: $client
--}}
@if($client->show_chat_button ?? true)
<div x-data="{ chatOpen: false }" class="relative">
    <button type="button" @click="chatOpen = !chatOpen" 
        class="{{ $btnClass ?? 'h-14 px-6 bg-emerald-500 text-white rounded-xl font-bold text-sm uppercase tracking-widest hover:bg-emerald-600 transition-all duration-300 hover:shadow-lg hover:shadow-emerald-200 hover:-translate-y-0.5 flex items-center justify-center gap-2' }}">
        <i class="fas fa-comments text-base"></i> Chat
    </button>

    {{-- Dropdown --}}
    <div x-show="chatOpen" @click.outside="chatOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 scale-95"
        class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 bg-white border border-slate-200 rounded-2xl shadow-xl p-4 min-w-[220px] z-50">
        
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3 text-center">Chat with us</p>
        
        @if(($client->show_whatsapp_button ?? false) && !empty($client->phone))
        @php
            $waPhone = $client->phone ?? '';
            $waPhone = preg_replace('/[^0-9]/', '', $waPhone);
        @endphp
        @if($waPhone)
        <a href="https://wa.me/{{$waPhone}}" target="_blank" 
            class="flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-50 transition group mb-2">
            <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center shrink-0 group-hover:scale-110 transition">
                <i class="fab fa-whatsapp text-white text-lg"></i>
            </div>
            <div>
                <span class="text-sm font-bold text-slate-800 block">WhatsApp</span>
                <span class="text-[11px] text-slate-400 font-medium">Message us instantly</span>
            </div>
        </a>
        @endif
        @endif

        @if($client->fb_page_id)
        <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" 
            class="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 transition group">
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center shrink-0 group-hover:scale-110 transition">
                <i class="fab fa-facebook-messenger text-white text-lg"></i>
            </div>
            <div>
                <span class="text-sm font-bold text-slate-800 block">Messenger</span>
                <span class="text-[11px] text-slate-400 font-medium">Chat on Facebook</span>
            </div>
        </a>
        @endif

        @if(!(($client->show_whatsapp_button ?? false) && !empty($client->phone)) && !$client->fb_page_id)
        <p class="text-sm text-slate-400 text-center py-2 font-medium">No chat channels available</p>
        @endif

        <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-4 h-4 bg-white border-r border-b border-slate-200 rotate-45"></div>
    </div>
</div>
@endif
