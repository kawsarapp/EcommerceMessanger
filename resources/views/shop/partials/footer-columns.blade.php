{{--
    Universal Footer Columns Partial
    ================================
    সব theme-এ include করুন:
    @include('shop.partials.footer-columns', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])

    Dashboard এর "Footer Content Manager" থেকে সব কিছু control হবে:
    - widgets.footer.brand_description — Brand description text
    - widgets.footer.menu1_title / menu2_title / menu3_title — Column headings (override)
    - widgets.footer.show_payment — Payment badges toggle
    - widgets.footer.show_social — Social icons toggle
    - footer_text — Copyright text
    - footer_links — Quick links (bottom bar)
    - widgets.trust_badges.items — Trust badge items (for homepage)
--}}

@php
    $footerDesc       = $client->widgets['footer']['brand_description'] ?? $client->description ?? $client->meta_description ?? '';
    $showPayment      = $client->widgets['footer']['show_payment'] ?? true;
    $showSocial       = $client->widgets['footer']['show_social'] ?? true;
    $footerCopyright  = $client->footer_text ?? ('© ' . date('Y') . ' ' . $client->shop_name . '. All Rights Reserved.');
    $deliveryText     = $client->widgets['delivery_time']['text'] ?? '';
    $officeHours      = $client->widgets['office_hours']['text'] ?? '';
    $contactTitle     = $client->widgets['footer']['contact_title'] ?? '';
    
    // Menu column headings — first check widget override, then footerMenu name, then default
    $col1Title = $client->widgets['footer']['menu1_title'] ?? ($footerMenu1->name ?? 'Information');
    $col2Title = $client->widgets['footer']['menu2_title'] ?? ($footerMenu2->name ?? 'Customer Service');
    $col3Title = $client->widgets['footer']['menu3_title'] ?? ($footerMenu3->name ?? 'Quick Links');
    
    // Social links — support both old and new field names
    $fbUrl    = $client->social_facebook ?? $client->facebook_url ?? null;
    $ytUrl    = $client->social_youtube  ?? $client->youtube_url  ?? null;
    $igUrl    = $client->social_instagram ?? $client->instagram_url ?? null;
    $tkUrl    = $client->tiktok_url ?? null;
@endphp

{{-- === COLUMN 1: Brand Info === --}}
<div class="footer-col-brand">
    {{-- Logo --}}
    <a href="{{ $baseUrl }}" class="inline-block mb-4">
        @if($client->logo)
            <img src="{{ asset('storage/'.$client->logo) }}" class="h-9 object-contain" alt="{{ $client->shop_name }}" loading="lazy">
        @else
            <span class="font-black text-xl">{{ $client->shop_name }}</span>
        @endif
    </a>
    
    {{-- Description --}}
    @if($footerDesc)
    <p class="text-sm leading-relaxed mb-4 opacity-75">{{ $footerDesc }}</p>
    @elseif($client->tagline)
    <p class="text-sm font-semibold mb-3 opacity-90">{{ $client->tagline }}</p>
    @endif
    
    {{-- Contact Info --}}
    <div class="space-y-2 mb-5 text-sm opacity-75">
        @if($client->phone)
        <div class="flex items-center gap-2">
            <i class="fas fa-phone-alt text-xs opacity-60"></i>
            <a href="tel:{{ $client->phone }}" class="hover:opacity-100 transition">{{ $client->phone }}</a>
        </div>
        @endif
        @if($client->email)
        <div class="flex items-center gap-2">
            <i class="fas fa-envelope text-xs opacity-60"></i>
            <a href="mailto:{{ $client->email }}" class="hover:opacity-100 transition">{{ $client->email }}</a>
        </div>
        @endif
        @if($deliveryText)
        <div class="flex items-center gap-2">
            <i class="fas fa-truck text-xs opacity-60"></i>
            <span>{{ $deliveryText }}</span>
        </div>
        @endif
        @if($officeHours)
        <div class="flex items-center gap-2">
            <i class="fas fa-clock text-xs opacity-60"></i>
            <span>{{ $officeHours }}</span>
        </div>
        @endif
        @if($client->address)
        <div class="flex items-start gap-2">
            <i class="fas fa-map-marker-alt text-xs opacity-60 mt-0.5"></i>
            <span>{{ $client->address }}</span>
        </div>
        @endif
    </div>
    
    {{-- Social Icons --}}
    @if($showSocial && ($fbUrl || $ytUrl || $igUrl || $tkUrl))
    <div class="flex gap-2 flex-wrap">
        @if($fbUrl)
        <a href="{{ $fbUrl }}" target="_blank" rel="noopener" class="footer-social-btn" title="Facebook">
            <i class="fab fa-facebook-f text-xs"></i>
        </a>
        @endif
        @if($ytUrl)
        <a href="{{ $ytUrl }}" target="_blank" rel="noopener" class="footer-social-btn" title="YouTube">
            <i class="fab fa-youtube text-xs"></i>
        </a>
        @endif
        @if($igUrl)
        <a href="{{ $igUrl }}" target="_blank" rel="noopener" class="footer-social-btn" title="Instagram">
            <i class="fab fa-instagram text-xs"></i>
        </a>
        @endif
        @if($tkUrl)
        <a href="{{ $tkUrl }}" target="_blank" rel="noopener" class="footer-social-btn" title="TikTok">
            <i class="fab fa-tiktok text-xs"></i>
        </a>
        @endif
    </div>
    @endif
</div>

{{-- === COLUMN 2: Footer Menu 1 === --}}
<div class="footer-col-menu">
    <h4 class="footer-col-heading">{{ $col1Title }}</h4>
    <div class="footer-col-links">
        @if(isset($footerMenu1) && $footerMenu1->items->count() > 0)
            @foreach($footerMenu1->items as $item)
                <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="footer-col-link">{{ $item->label }}</a>
            @endforeach
        @endif
    </div>
</div>

{{-- === COLUMN 3: Footer Menu 2 === --}}
<div class="footer-col-menu">
    <h4 class="footer-col-heading">{{ $col2Title }}</h4>
    <div class="footer-col-links">
        @if(isset($footerMenu2) && $footerMenu2->items->count() > 0)
            @foreach($footerMenu2->items as $item)
                <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="footer-col-link">{{ $item->label }}</a>
            @endforeach
        @else
            <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="footer-col-link">Track Order</a>
        @endif
    </div>
</div>

{{-- === COLUMN 4: Footer Menu 3 === --}}
<div class="footer-col-menu">
    <h4 class="footer-col-heading">{{ $col3Title }}</h4>
    <div class="footer-col-links">
        @if(isset($footerMenu3) && $footerMenu3->items->count() > 0)
            @foreach($footerMenu3->items as $item)
                <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="footer-col-link">{{ $item->label }}</a>
            @endforeach
        @endif
    </div>
</div>

{{-- === COLUMN 5: Payment + Quick Links === --}}
@if($showPayment)
<div class="footer-col-payment">
    <h4 class="footer-col-heading">Payment Options</h4>
    <div class="flex flex-wrap gap-2 mb-4">
        @if($client->cod_active)
        <span class="footer-pay-badge">COD</span>
        @endif
        @if($client->isPaymentGatewayActive('bkash_pgw') || $client->isPaymentGatewayActive('bkash_merchant') || $client->isPaymentGatewayActive('bkash_personal'))
        <span class="footer-pay-badge text-pink-400">bKash</span>
        @endif
        @if($client->isPaymentGatewayActive('sslcommerz'))
        <span class="footer-pay-badge text-green-400">SSL</span>
        @endif
        @if($client->isPaymentGatewayActive('surjopay'))
        <span class="footer-pay-badge text-yellow-400">Surjopay</span>
        @endif
        @if($client->partial_payment_active || $client->full_payment_active)
        <span class="footer-pay-badge text-blue-400">Mobile Bank</span>
        @endif
    </div>
</div>
@endif
