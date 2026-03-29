<!-- Offer Popup Banner -->
@php
    $showPopup = false;
    if (!empty($client->popup_active) && (!$client->popup_expires_at || \Carbon\Carbon::parse($client->popup_expires_at)->isFuture())) {
        $popupPages = $client->popup_pages ?? [];
        if (empty($popupPages)) {
            $showPopup = true;
        } else {
            $currentRoute = request()->route() ? request()->route()->getName() : '';
            if (in_array('home', $popupPages) && in_array($currentRoute, ['shop.show'])) $showPopup = true;
            if (in_array('product', $popupPages) && in_array($currentRoute, ['shop.product.details', 'shop.product.custom'])) $showPopup = true;
            if (in_array('checkout', $popupPages) && in_array($currentRoute, ['shop.checkout', 'shop.checkout.custom'])) $showPopup = true;
        }
    }
    $delaySeconds = $client->popup_delay ?? 3;
    $delayMs = $delaySeconds * 1000;
@endphp

@if($showPopup)
<div x-data="{ open: false, init() { setTimeout(() => this.open = true, {{ $delayMs }}); } }" 
     x-show="open" 
     x-transition:enter="transition ease-out duration-500"
     x-transition:enter-start="opacity-0 scale-90 translate-y-8"
     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
     x-transition:leave-end="opacity-0 scale-95 translate-y-8"
     class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 sm:p-6" x-cloak>
     
    <div class="bg-white rounded-[2rem] shadow-float max-w-lg w-full relative overflow-hidden ring-1 ring-slate-900/5" @click.away="open = false">
        
        <button @click="open = false" class="absolute top-4 right-4 w-10 h-10 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-white z-10 transition-all shadow-sm">
            <i class="fas fa-times text-lg"></i>
        </button>
        
        <div class="flex flex-col h-full w-full">
            @if(!empty($client->popup_link))
            <a href="{{ $client->popup_link }}" target="_blank" class="block w-full">
            @endif
            
                @if(!empty($client->popup_image))
                <div class="w-full relative bg-slate-50">
                    <img src="{{ asset('storage/' . $client- loading="lazy">popup_image) }}" class="w-full h-auto max-h-[40vh] object-contain sm:object-cover">
                </div>
                @endif
                
                @if(!empty($client->popup_title) || !empty($client->popup_description))
                <div class="p-6 sm:p-8 text-center bg-white flex-1 flex flex-col justify-center">
                    @if(!empty($client->popup_title))
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-3 tracking-tight">{{ $client->popup_title }}</h2>
                    @endif
                    
                    @if(!empty($client->popup_description))
                    <p class="text-sm sm:text-base text-slate-500 font-medium leading-relaxed">{{ $client->popup_description }}</p>
                    @endif
                    
                    @if(!empty($client->popup_link))
                    <div class="mt-6">
                        <span class="inline-flex items-center justify-center gap-2 bg-primary text-white font-bold px-8 py-3.5 rounded-xl shadow-sm hover:shadow-md hover:bg-primary-dark hover:-translate-y-0.5 transition-all uppercase tracking-wider text-sm w-full sm:w-auto">
                            Learn More <i class="fas fa-arrow-right text-xs"></i>
                        </span>
                    </div>
                    @endif
                </div>
                @endif
                
            @if(!empty($client->popup_link))
            </a>
            @endif
        </div>
    </div>
</div>
@endif
