{{--
    Universal Popup Banner Partial
    ================================
    Include this just before </body> in any theme layout.
    Requires: $client
    Usage: @include('shop.partials.popup-banner', ['client' => $client])
--}}
@if($client->popup_active)
@php
    $showPopup = true;
    if (!empty($client->popup_expires_at) && \Carbon\Carbon::now()->greaterThan(\Carbon\Carbon::parse($client->popup_expires_at))) {
        $showPopup = false;
    }
    if (!empty($client->popup_pages)) {
        $pages = is_array($client->popup_pages) ? $client->popup_pages : (json_decode((string)$client->popup_pages, true) ?? []);
        if (!empty($pages)) {
            $currentRoute = request()->route()->getName();
            $isHome     = str_contains($currentRoute, 'show') && !str_contains($currentRoute, 'product') && !str_contains($currentRoute, 'checkout');
            $isProduct  = str_contains($currentRoute, 'product');
            $isCheckout = str_contains($currentRoute, 'checkout');
            if (($isHome && !in_array('home', $pages)) || ($isProduct && !in_array('product', $pages)) || ($isCheckout && !in_array('checkout', $pages))) {
                $showPopup = false;
            }
        }
    }
@endphp
@if($showPopup)
<div x-data="{ open: false }"
     x-init="setTimeout(() => { open = true }, {{ ($client->popup_delay ?? 3) * 1000 }})"
     x-cloak x-show="open"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div @click.outside="open = false"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden relative">
        <button @click="open = false"
                class="absolute top-3 right-3 w-8 h-8 bg-black/40 hover:bg-red-500 text-white rounded-full flex items-center justify-center transition z-10 shadow">
            <i class="fas fa-times text-sm"></i>
        </button>
        @if($client->popup_link)<a href="{{ $client->popup_link }}" class="block">@endif
        @if($client->popup_image)
            <img src="{{ asset('storage/'.$client->popup_image) }}" class="w-full h-auto max-h-[350px] object-cover" alt="{{ $client->popup_title ?? '' }}">
        @endif
        @if($client->popup_title || $client->popup_description)
            <div class="p-6 text-center">
                @if($client->popup_title)<h2 class="text-xl font-black text-gray-800 mb-2">{{ $client->popup_title }}</h2>@endif
                @if($client->popup_description)<p class="text-sm text-gray-600 mb-4">{{ $client->popup_description }}</p>@endif
                @if($client->popup_link)
                    <span class="inline-block text-white px-6 py-2 rounded-full font-bold text-xs uppercase shadow transition"
                          style="background-color: {{ $client->primary_color ?? '#e31e24' }};">Explore Now</span>
                @endif
            </div>
        @endif
        @if($client->popup_link)</a>@endif
    </div>
</div>
@endif
@endif
