@if($client->widget('show_floating_chat', true) && ($client->show_chat_button ?? true))
@php
    $wa = ($client->show_whatsapp_button ?? true) && $client->is_whatsapp_active && $client->wa_status === 'connected' && ($client->phone ?? false);
    $ms = ($client->show_messenger_button ?? true) && ($client->fb_page_id ?? false);
    $phone = preg_replace('/[^0-9]/', '', $client->phone ?? '');
@endphp

@if($wa || $ms)
<div x-data="{open:false}" class="fixed bottom-20 md:bottom-6 right-4 md:right-6 z-[9999]" x-cloak>
    
    {{-- Button --}}
    <button @click="open=!open" class="w-14 h-14 md:w-16 md:h-16 rounded-full shadow-xl hover:shadow-2xl hover:scale-110 transition-all duration-300 flex items-center justify-center relative overflow-hidden"
        style="background:linear-gradient(135deg,#22c55e,#16a34a)">
        <i class="fab fa-whatsapp text-2xl md:text-3xl text-white" x-show="!open"></i>
        <i class="fas fa-times text-xl md:text-2xl text-white" x-show="open"></i>
        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full animate-ping border-2 border-white" x-show="!open"></span>
        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full border-2 border-white" x-show="!open"></span>
    </button>

    {{-- Popup --}}
    <div x-show="open" @click.outside="open=false"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute bottom-16 md:bottom-20 right-0 bg-white rounded-2xl shadow-2xl border border-gray-100 w-72 md:w-80 overflow-hidden">
        
        {{-- Header --}}
        <div class="bg-gradient-to-r from-green-500 to-emerald-500 text-white p-5">
            <h4 class="font-bold text-lg">{{ $client->shop_name }}</h4>
            <p class="text-green-100 text-xs mt-1">সাধারণত ১ ঘণ্টার মধ্যে রিপ্লাই দিই</p>
        </div>
        
        <div class="p-4 space-y-2">
            @if($wa)
            <a href="https://wa.me/{{ $phone }}?text={{ urlencode('আস-সালামু আলাইকুম! আপনাদের শপ থেকে অর্ডার করতে চাই।') }}" target="_blank"
                class="flex items-center gap-3 p-3 rounded-xl bg-green-50 hover:bg-green-100 transition group">
                <div class="w-11 h-11 bg-green-500 rounded-full flex items-center justify-center shrink-0">
                    <i class="fab fa-whatsapp text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <span class="text-sm font-bold text-gray-800 block">WhatsApp</span>
                    <span class="text-[11px] text-gray-500">এখনই মেসেজ করুন</span>
                </div>
                <i class="fas fa-chevron-right text-gray-300 group-hover:translate-x-1 transition"></i>
            </a>
            @endif

            @if($ms)
            <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank"
                class="flex items-center gap-3 p-3 rounded-xl bg-blue-50 hover:bg-blue-100 transition group">
                <div class="w-11 h-11 bg-blue-600 rounded-full flex items-center justify-center shrink-0">
                    <i class="fab fa-facebook-messenger text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <span class="text-sm font-bold text-gray-800 block">Messenger</span>
                    <span class="text-[11px] text-gray-500">ফেসবুকে চ্যাট করুন</span>
                </div>
                <i class="fas fa-chevron-right text-gray-300 group-hover:translate-x-1 transition"></i>
            </a>
            @endif
        </div>
        
        <div class="px-4 pb-3 text-center">
            <span class="text-[10px] text-gray-400">Powered by AI Commerce</span>
        </div>
    </div>
</div>
@endif
@endif

{{-- AI Chat Widget --}}
@php $token = trim($client->api_token ?? ''); @endphp
@if(!empty($token) && ($client->is_ai_enabled ?? false) && ($client->show_ai_chat_widget ?? true))
<script>
window.AICB_KEY='{!! addslashes($token) !!}';
window.AICB_URL='{{ rtrim(url("/"), "/") }}';
window.AICB_SHOP=@json($client->widget_name ?: $client->shop_name);
window.AICB_COLOR='{{ $client->primary_color ?? "#f85606" }}';
window.AICB_POSITION='{{ $client->widget_position ?? "bottom-right" }}';
window.AICB_PRE_CHAT={{ ($client->require_pre_chat_form ?? false) ? 'true' : 'false' }};
@if($client->widget_greeting)window.AICB_GREETING=@json($client->widget_greeting);@endif
</script>
<script src="{{ asset('js/chatbot-widget.js?v=1.5') }}" defer></script>
@endif
