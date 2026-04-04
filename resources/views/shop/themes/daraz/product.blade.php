@extends('shop.themes.daraz.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
    $baseUrl = $client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<div class="bg-gray-100 py-6"  >
    <div class="max-w-7xl mx-auto px-4 md:px-6" x-data="{ mainImg: '{{ asset('storage/'.($product->thumbnail ?? 'images/placeholder.png')) }}' }">
        
        {{-- Breadcrumb --}}
        <div class="text-sm text-gray-500 mb-4 flex items-center gap-2">
            <a href="{{$baseUrl}}" class="hover:text-primary transition">হোম</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-gray-400">{{$product->category->name ?? 'General'}}</span>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-gray-900 font-medium truncate max-w-[200px]">{{$product->name}}</span>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12">
                
                {{-- Left: Images (4 cols) --}}
                <div class="md:col-span-5 lg:col-span-4 flex flex-col gap-4">
                    <div class="aspect-square bg-white border border-gray-200 rounded-lg overflow-hidden relative group">
                        @if($product->sale_price)
                        <div class="absolute top-4 right-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded">
                            -{{ round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100) }}%
                        </div>
    @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])

                        @endif
                        <img :src="mainImg" class="w-full h-full object-contain cursor-zoom-in" loading="lazy">
                    </div>
                    
                    {{-- Thumbnails --}}
                    <div class="flex gap-2 overflow-x-auto hide-scroll pb-2">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" 
                             @click="mainImg = $el.src" 
                             :class="{ 'border-primary': mainImg === '{{asset('storage/'.$product->thumbnail)}}', 'border-transparent opacity-70': mainImg !== '{{asset('storage/'.$product->thumbnail)}}' }"
                             class="w-16 h-16 object-cover border-2 rounded cursor-pointer hover:border-primary transition p-1">
                        
                        @foreach($product->gallery ?? [] as $img)
                        <img src="{{asset('storage/'.$img)}}" 
                             @click="mainImg = $el.src" 
                             :class="{ 'border-primary': mainImg === '{{asset('storage/'.$img)}}', 'border-transparent opacity-70': mainImg !== '{{asset('storage/'.$img)}}' }"
                             class="w-16 h-16 object-cover border-2 rounded cursor-pointer hover:border-primary transition p-1" loading="lazy">
                        @endforeach
                    </div>

                    @if($product->video_url)
                    <a href="{{$product->video_url}}" target="_blank" class="w-full mt-2 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 py-2.5 rounded flex items-center justify-center font-bold text-sm transition">
                        <i class="fab fa-youtube text-lg mr-2"></i> ভিডিও দেখুন (Watch Video)
                    </a>
                    @endif
                </div>

                {{-- Middle: Info (5 cols) --}}
                <div class="md:col-span-7 lg:col-span-5 flex flex-col">
                    @if($product->brand)
                    <div class="text-xs font-bold text-gray-400 tracking-widest uppercase mb-1 flex items-center gap-1.5">
                        <i class="fas fa-tag"></i> {{$product->brand}}
                    </div>
                    @endif
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 mb-2 leading-snug">{{$product->name}}</h1>
                    
                    {{-- Ratings & Brand --}}
                    <div class="flex items-center gap-4 text-sm mb-4">
                        @php 
                            $avgRating = $product->reviews()->where('is_visible', true)->avg('rating') ?? 0;
                            $reviewCount = $product->reviews()->where('is_visible', true)->count();
                        @endphp
                        <div class="flex items-center text-amber-400 text-xs">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="{{ $i <= round($avgRating) ? 'fas' : 'far' }} fa-star"></i>
                            @endfor
                            <a href="#reviews" class="text-blue-500 hover:underline ml-2">{{$reviewCount}} Ratings</a>
                        </div>
                    </div>

                    <hr class="border-gray-100 mb-4">

                    {{-- Pricing (Daraz orange style) --}}
                    <div class="mb-6">
                        <span class="text-3xl font-black text-primary block" x-text="'৳' + new Intl.NumberFormat('en-IN').format(currentPrice)">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                        @if($product->sale_price)
                        <div class="flex items-center gap-2 mt-1">
                            <del class="text-gray-400 text-sm">৳{{number_format($product->regular_price)}}</del>
                            <span class="text-sm font-bold text-gray-800">-{{ round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100) }}%</span>
                        </div>
                        @endif
                    </div>

                    @include('shop.partials.product-variations')
                </div>

                {{-- Right: Delivery Options (3 cols) --}}
                <div class="hidden lg:block lg:col-span-3">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <div class="text-xs font-bold text-gray-500 mb-4 uppercase tracking-wider">ডেলিভারি অপশন</div>
                        
                        <div class="flex gap-3 mb-4">
                            <i class="fas fa-map-marker-alt text-gray-400 mt-0.5"></i>
                            <div class="text-sm text-gray-700">
                                সারা দেশে হোম ডেলিভারি
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex gap-3">
                                <i class="fas fa-truck text-gray-400 mt-0.5"></i>
                                <div>
                                    <div class="text-sm font-bold text-gray-800">স্ট্যান্ডার্ড ডেলিভারি</div>
                                    <div class="text-xs text-gray-500">২ থেকে ৩ দিন</div>
                                </div>
                            </div>
                            <span class="text-sm font-bold text-gray-900">৳{{$client->delivery_charge_inside ?? 60}}</span>
                        </div>
                        
                        <div class="flex gap-3 mb-4">
                            <i class="fas fa-money-bill-wave text-gray-400 mt-0.5"></i>
                            <div class="text-sm text-gray-700">ক্যাশ অন ডেলিভারি চালু আছে</div>
                        </div>

                        <hr class="border-gray-200 my-4">
                        
                        <div class="text-xs font-bold text-gray-500 mb-4 uppercase tracking-wider">রিটার্ন ও ওয়ারেন্টি</div>

                        @if($client->show_return_warranty ?? true)
                        <div class="flex gap-3 mb-4">
                            <i class="fas fa-undo text-gray-400 mt-0.5"></i>
                            <div class="text-sm text-gray-700">{{$product->return_policy ?? '7 Days Returns'}}</div>
                        </div>
                        <div class="flex gap-3">
                            <i class="fas fa-shield-alt text-gray-400 mt-0.5"></i>
                            <div class="text-sm text-gray-700">{{$product->warranty ?? 'Warranty not available'}}</div>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- Details Sections --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-6">
            <div class="lg:col-span-3 space-y-6">
                {{-- Description --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">বিস্তারিত বিবরন</h2>
                    @if($product->material)
                    <div class="mb-4 text-sm text-gray-600 bg-gray-50 border border-gray-100 p-3 rounded flex gap-2">
                        <strong class="text-gray-800">ম্যাটেরিয়াল (Material):</strong> {{$product->material}}
                    </div>
                    @endif
                    <div class="prose max-w-none text-sm text-gray-700 bg-gray-50 p-4 rounded-lg">
                        {!! clean($product->description ?? $product->long_description) !!}
                    </div>
                </div>
                
                {{-- Reviews Component --}}
                @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
            </div>
            
            {{-- Right Sidebar Recommendations --}}
            @if($client->show_related_products ?? true)
            <div class="lg:col-span-1 border border-gray-200 rounded-xl bg-white p-4 hidden lg:block">
                <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase">আপনার পছন্দ হতে পারে</h3>
                <div class="flex flex-col gap-4">
                    @php $related = App\Models\Product::where('client_id', $client->id)->where('category_id', $product->category_id)->where('id', '!=', $product->id)->limit(4)->get(); @endphp
                    @foreach($related as $r)
                    <a href="{{$baseUrl.'/product/'.$r->slug}}" class="flex gap-3 group">
                        <img src="{{asset('storage/'.$r->thumbnail)}}" class="w-16 h-16 object-cover rounded border border-gray-100">
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-700 group-hover:text-primary transition line-clamp-2 leading-tight">{{$r->name}}</span>
                            <span class="text-primary font-bold text-sm mt-1">৳{{$r->sale_price ?? $r->regular_price}}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

    </div>
</div>
@include('shop.partials.product-sticky-bar')
@endsection
