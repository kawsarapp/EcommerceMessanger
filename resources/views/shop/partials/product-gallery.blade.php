@php
    // Normalize gallery images
    $images = [];
    if($product->thumbnail) {
        $images[] = asset('storage/'.$product->thumbnail);
    }
    if($product->gallery) {
        $decoded = is_string($product->gallery) ? json_decode($product->gallery, true) : $product->gallery;
        if(is_array($decoded)) {
            foreach($decoded as $img) {
                $images[] = asset('storage/'.$img);
            }
        }
    }
    // Fallback if empty
    if(count($images) === 0) {
        $images[] = asset('images/placeholder.png');
    }
@endphp

<div class="product-gallery-wrapper w-full" x-data="{ activeImage: '{{ $images[0] }}', isZoomed: false, xPos: 0, yPos: 0 }">
    {{-- Main Image --}}
    <div class="relative w-full aspect-square border border-gray-100 bg-white rounded-xl overflow-hidden mb-3 group cursor-crosshair">
        
        {{-- Standard View --}}
        <img :src="activeImage" alt="{{ $product->name }}" 
             class="w-full h-full object-contain transition-opacity duration-300"
             x-show="!isZoomed"
             @mouseenter="isZoomed = true"
             x-cloak>

        {{-- Magnified View on Hover --}}
        <img :src="activeImage" alt="{{ $product->name }}" 
             class="absolute inset-0 w-full h-full object-cover origin-top-left transition-transform duration-75 ease-out scale-[1.8]"
             x-show="isZoomed"
             @mousemove="xPos = (($event.offsetX / $el.offsetWidth) * 100); yPos = (($event.offsetY / $el.offsetHeight) * 100); $el.style.transformOrigin = xPos + '% ' + yPos + '%'"
             @mouseleave="isZoomed = false"
             x-cloak>
             
        {{-- Discount Badge overlay --}}
        @if($product->discount_amount > 0)
            <div class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded shadow-sm z-10">
                @if($product->discount_type == 'percentage')
                    {{ $product->discount_amount }}% OFF
                @else
                    ৳{{ number_format($product->discount_amount) }} OFF
                @endif
            </div>
        @endif
    </div>

    {{-- Thumbnails Scrollable Row --}}
    @if(count($images) > 1)
    <div class="flex gap-2 overflow-x-auto hide-scroll py-2 snap-x">
        @foreach($images as $img)
        <button @click="activeImage = '{{ $img }}'" 
                :class="activeImage === '{{ $img }}' ? 'border-primary ring-1 ring-primary shadow-sm' : 'border-gray-200 opacity-75 hover:opacity-100'"
                class="relative w-16 h-16 sm:w-20 sm:h-20 shrink-0 border rounded-lg bg-white overflow-hidden transition-all duration-200 snap-center">
            <img src="{{ $img }}" class="w-full h-full object-contain mix-blend-multiply p-1">
        </button>
        @endforeach
    </div>
    @endif
</div>

<style>
.hide-scroll::-webkit-scrollbar { display: none; }
.hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
</style>
