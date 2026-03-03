@extends('shop.layout')

@section('title', $product->name . ' - ' . $client->shop_name)

@section('content')
<div x-data="{ 
    mainImage: '{{ asset('storage/' . $product->thumbnail) }}',
    selectedColor: null,
    selectedSize: null,
    showVideoModal: false,
    showZoomModal: false,
    activeVideoUrl: '',

    playVideo(url) {
        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            if (match && match[2].length === 11) {
                this.activeVideoUrl = 'https://www.youtube.com/embed/' + match[2] + '?autoplay=1&rel=0';
            } else {
                this.activeVideoUrl = url;
            }
        } else {
            this.activeVideoUrl = url;
        }
        this.showVideoModal = true;
    }
}">

<main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 mb-24 md:mb-0">

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

<!-- IMAGE SECTION -->
<div class="space-y-4">
<div class="aspect-square bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 relative group cursor-zoom-in"
     @click="showZoomModal = true">

<img :src="mainImage"
     class="w-full h-full object-contain p-2 md:p-6 group-hover:scale-105 transition-transform duration-500">

@if($product->sale_price && $product->regular_price > $product->sale_price)
<span class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg animate-pulse">
-{{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}% OFF
</span>
@endif

</div>

<!-- GALLERY -->
<div class="flex gap-3 overflow-x-auto py-2">
<div @click="mainImage = '{{ asset('storage/' . $product->thumbnail) }}'"
     class="w-20 h-20 rounded-xl border-2 cursor-pointer overflow-hidden bg-white p-1 transition-all"
     :class="mainImage === '{{ asset('storage/' . $product->thumbnail) }}' ? 'border-primary ring-2 ring-primary/20 scale-95' : 'border-gray-200'">
<img src="{{ asset('storage/' . $product->thumbnail) }}" class="w-full h-full object-cover rounded-lg">
</div>

@if($product->gallery)
@foreach($product->gallery as $img)
<div @click="mainImage = '{{ asset('storage/' . $img) }}'"
     class="w-20 h-20 rounded-xl border-2 cursor-pointer overflow-hidden bg-white p-1 transition-all"
     :class="mainImage === '{{ asset('storage/' . $img) }}' ? 'border-primary ring-2 ring-primary/20 scale-95' : 'border-gray-200'">
<img src="{{ asset('storage/' . $img) }}" class="w-full h-full object-cover rounded-lg">
</div>
@endforeach
@endif
</div>
</div>

<!-- PRODUCT INFO -->
<div>
<h1 class="text-3xl font-bold mb-4">{{ $product->name }}</h1>

<div class="text-3xl font-bold text-primary mb-6">
৳{{ number_format($product->sale_price ?? $product->regular_price) }}
</div>

<!-- COLORS -->
@if($product->colors)
@php $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors; @endphp
<div class="mb-6">
<h3 class="font-bold mb-3">Select Color</h3>
<div class="flex flex-wrap gap-3">

@foreach($colors as $color)
<button @click="selectedColor = '{{ $color }}'"
class="px-4 py-2 rounded-xl border font-bold flex items-center gap-2 transition-all"
:class="selectedColor === '{{ $color }}' ? 'border-primary bg-primary text-white' : 'border-gray-200 text-gray-700'">

<span x-show="selectedColor !== '{{ $color }}'"
class="w-3 h-3 rounded-full border"
style="background-color: {{ strtolower($color) }}"></span>

<i x-show="selectedColor === '{{ $color }}'" class="fas fa-check text-xs"></i>

{{ $color }}
</button>
@endforeach

</div>
</div>
@endif

<!-- SIZES -->
@if($product->sizes)
@php $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes; @endphp
<div class="mb-6">
<h3 class="font-bold mb-3">Select Size</h3>
<div class="flex flex-wrap gap-3">

@foreach($sizes as $size)
<button @click="selectedSize = '{{ $size }}'"
class="min-w-[3.5rem] h-12 px-3 rounded-xl border font-bold transition-all"
:class="selectedSize === '{{ $size }}' ? 'border-primary bg-primary text-white' : 'border-gray-200 text-gray-700'">
{{ $size }}
</button>
@endforeach

</div>
</div>
@endif

<!-- DESCRIPTION -->
<div class="prose max-w-none">
{!! $product->description ?? $product->short_description !!}
</div>

<!-- ORDER BUTTON -->
<div class="mt-8 flex gap-4">

<a :href="'https://m.me/{{ $client->fb_page_id }}?text=' + 
encodeURIComponent(
'I want to buy: {{ $product->name }} (Code: {{ $product->sku }})' + 
(selectedColor ? ' Color: ' + selectedColor : '') + 
(selectedSize ? ' Size: ' + selectedSize : '')
)"
target="_blank"
class="flex-1 bg-primary text-white py-4 rounded-xl font-bold text-center">
Order Now
</a>

</div>

</div>
</div>
</main>

<!-- ZOOM MODAL -->
<div x-show="showZoomModal"
class="fixed inset-0 bg-black/90 flex items-center justify-center"
x-transition>
<img :src="mainImage" class="max-h-full max-w-full object-contain">
</div>

</div>
@endsection