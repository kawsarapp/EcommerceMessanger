{{--
    Universal Dynamic Footer Extras Partial
    ==========================================
    Injects: social links, payment methods, cart count script, footer copyright.
    Include inside the footer section of any theme.
    Requires: $client, $baseUrl, $clean
    Usage: @include('shop.partials.dynamic-footer-extras', ['client' => $client, 'baseUrl' => $baseUrl ?? '', 'clean' => $clean ?? ''])
--}}
@php
    $footerCopyright  = $client->footer_text ?? ('© ' . date('Y') . ' ' . $client->shop_name . '. All Rights Reserved.');
    $footerShowSocial = $client->widgets['footer']['show_social'] ?? true;
    $footerShowPay    = $client->widgets['footer']['show_payment'] ?? true;
    $activeMethods    = $client->getActivePaymentMethods();
@endphp

{{-- Dynamic Social Links --}}
@if($footerShowSocial && ($client->social_facebook || $client->social_instagram || $client->social_youtube || $client->facebook_url || $client->instagram_url || $client->youtube_url || $client->tiktok_url))
<div class="flex gap-3 flex-wrap mt-4" id="dynamic-social-links">
    @if($client->social_facebook ?? $client->facebook_url)
    <a href="{{ $client->social_facebook ?? $client->facebook_url }}" target="_blank"
       class="w-9 h-9 bg-[#3b5998] text-white flex items-center justify-center rounded-full hover:opacity-80 transition shadow-sm">
       <i class="fab fa-facebook-f text-sm"></i>
    </a>
    @endif
    @if($client->social_youtube ?? $client->youtube_url)
    <a href="{{ $client->social_youtube ?? $client->youtube_url }}" target="_blank"
       class="w-9 h-9 bg-[#ff0000] text-white flex items-center justify-center rounded-full hover:opacity-80 transition shadow-sm">
       <i class="fab fa-youtube text-sm"></i>
    </a>
    @endif
    @if($client->social_instagram ?? $client->instagram_url)
    <a href="{{ $client->social_instagram ?? $client->instagram_url }}" target="_blank"
       class="w-9 h-9 bg-gradient-to-br from-purple-500 to-pink-500 text-white flex items-center justify-center rounded-full hover:opacity-80 transition shadow-sm">
       <i class="fab fa-instagram text-sm"></i>
    </a>
    @endif
    @if($client->tiktok_url)
    <a href="{{ $client->tiktok_url }}" target="_blank"
       class="w-9 h-9 bg-black text-white flex items-center justify-center rounded-full hover:opacity-80 transition shadow-sm border border-gray-700">
       <i class="fab fa-tiktok text-sm"></i>
    </a>
    @endif
</div>
@endif

{{-- Dynamic Payment Methods --}}
@if($footerShowPay && count($activeMethods) > 0)
<div class="flex flex-wrap gap-2 mt-4" id="dynamic-payment-methods">
    @foreach($activeMethods as $key => $label)
    <span class="text-[10px] font-bold px-2 py-1 rounded-sm border
        @if($key === 'cod') bg-green-50 text-green-700 border-green-200
        @elseif(str_contains($key, 'bkash')) bg-pink-50 text-pink-700 border-pink-200
        @elseif($key === 'sslcommerz') bg-blue-50 text-blue-700 border-blue-200
        @else bg-gray-100 text-gray-600 border-gray-200 @endif">
        {{ $label }}
    </span>
    @endforeach
</div>
@endif

{{-- Dynamic Footer Copyright --}}
<div class="mt-6 pt-4 border-t border-white/10 text-xs text-gray-400" id="dynamic-footer-copyright">
    {!! nl2br(e($footerCopyright)) !!}
    @if(!empty($client->footer_links) && is_array($client->footer_links))
    <div class="flex gap-4 flex-wrap mt-2">
        @foreach($client->footer_links as $link)
        <a href="{{ $link['url'] ?? '#' }}" class="hover:text-white transition">{{ $link['title'] ?? '' }}</a>
        @endforeach
    </div>
    @endif
</div>

{{-- Cart Count Live Badge Script (updates all .cart-count elements on the page) --}}
<script>
(function() {
    const cartCount = {{ session()->has('cart') ? count(session()->get('cart')) : 0 }};
    document.querySelectorAll('.cart-count-badge').forEach(el => {
        el.textContent = cartCount;
        el.style.display = cartCount > 0 ? 'flex' : 'flex';
    });
})();
</script>
