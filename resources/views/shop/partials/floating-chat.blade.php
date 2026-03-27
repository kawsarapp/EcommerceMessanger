{--
    Floating Chat Widget - Fixed bottom-right corner
    Required: $client
    Fully Responsive for Mobile, Tablet, Desktop
--}}
@if($client->widget('show_floating_chat', true) && ($client->show_chat_button ?? true))
@php
    $hasWhatsApp  = ($client->show_whatsapp_button  ?? true)
                    && $client->is_whatsapp_active
                    && $client->wa_status === 'connected'
                    && ($client->phone ?? false);
    $hasMessenger = ($client->show_messenger_button ?? true)
                    && ($client->fb_page_id ?? false);
    $waPhone = preg_replace('/[^0-9]/', '', $client->phone ?? '');
@endphp

@if($hasWhatsApp || $hasMessenger)
<div x-data="{ open: false }" class="fixed bottom-20 md:bottom-6 right-4 md:right-6 z-[9999]" x-cloak>
    
    {{-- Toggle Button --}}
    <button @click="open = !open" 
        class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-xl hover:shadow-2xl hover:scale-110 transition-all duration-300 flex items-center justify-center relative">
        <i class="fab fa-whatsapp text-2xl md:text-3xl" x-show="!open"></i>
        <i class="fas fa-times text-xl md:text-2xl" x-show="open"></i>
        {{-- Notification Dot --}}
        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full animate-pulse border-2 border-white" x-show="!open"></span>
    </button>

    {{-- Popup --}}
    <div x-show="open" 
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute bottom-16 md:bottom-20 right-0 bg-white rounded-2xl shadow-2xl border border-slate-200 w-72 md:w-80 overflow-hidden">
        
        {{-- Header --}}
        <div class="bg-gradient-to-r from-emerald-500 to-green-500 text-white p-5">
            <h4 class="font-bold text-lg font-bangla">{{ $client->shop_name }}</h4>
            <p class="text-emerald-100 text-xs font-medium mt-1 font-bangla">সাধারণত ১ ঘণ্টার মধ্যে রিপ্লাই দিই</p>
        </div>
        
        <div class="p-4 space-y-3">
            {{-- WhatsApp --}}
            @if($hasWhatsApp)
            <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode('আস-সালামু আলাইকুম! আপনাদের শপ থেকে অর্ডার করতে চাই।') }}" target="_blank"
                class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 transition group">
                <div class="w-11 h-11 bg-emerald-500 rounded-full flex items-center justify-center shrink-0">
                    <i class="fab fa-whatsapp text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <span class="text-sm font-bold text-slate-800 block">WhatsApp</span>
                    <span class="text-[11px] text-slate-500 font-bangla">এখনই মেসেজ করুন</span>
                </div>
                <i class="fas fa-chevron-right text-slate-400 ml-auto text-xs group-hover:translate-x-1 transition"></i>
            </a>
            @endif

            {{-- Messenger --}}
            @if($hasMessenger)
            <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank"
                class="flex items-center gap-3 p-3 rounded-xl bg-blue-50 hover:bg-blue-100 transition group">
                <div class="w-11 h-11 bg-blue-600 rounded-full flex items-center justify-center shrink-0">
                    <i class="fab fa-facebook-messenger text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <span class="text-sm font-bold text-slate-800 block">Messenger</span>
                    <span class="text-[11px] text-slate-500 font-bangla">ফেসবুকে চ্যাট করুন</span>
                </div>
                <i class="fas fa-chevron-right text-slate-400 ml-auto text-xs group-hover:translate-x-1 transition"></i>
            </a>
            @endif
        </div>
        
        <div class="px-4 pb-3">
            <p class="text-[10px] text-slate-400 text-center font-medium">Powered by AI Commerce Bot</p>
        </div>
    </div>
</div>
@endif
@endif

{{-- AI Live Chat Widget --}}
@php
    $apiToken = trim($client->api_token ?? '');
@endphp
@if(!empty($apiToken) && ($client->is_ai_enabled ?? false) && ($client->show_ai_chat_widget ?? true))
<script>
window.AICB_KEY      = '{!! addslashes($apiToken) !!}';
window.AICB_URL      = '{{ rtrim(url("/"), "/") }}';
window.AICB_SHOP     = @json($client->widget_name ?: $client->shop_name);
window.AICB_COLOR    = '{{ $client->primary_color ?? "#4f46e5" }}';
window.AICB_POSITION = '{{ $client->widget_position ?? "bottom-right" }}';
window.AICB_PRE_CHAT = {{ ($client->require_pre_chat_form ?? false) ? 'true' : 'false' }};
@if($client->widget_greeting)
window.AICB_GREETING = @json($client->widget_greeting);
@endif
</script>
<script src="{{ asset('js/chatbot-widget.js?v=1.5') }}" defer></script>
@endif
