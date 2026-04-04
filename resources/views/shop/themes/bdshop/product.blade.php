@extends('shop.themes.bdshop.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug);
$reviews = $product->reviews()->where('is_visible', true)->get();
$avgRating = $reviews->avg('rating') ?? 0;
$totalReviews = $reviews->count();
@endphp

<main class="max-w-[1280px] mx-auto px-4 py-4 sm:py-8" x-data="{ mainImg: '{{asset('storage/'.$product->thumbnail)}}', qty: 1, color: '', size: '' }">
    
    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center text-xs text-slate-500 font-medium overflow-hidden">
        <a href="{{$baseUrl}}" class="hover:text-primary transition">হোম</a>
        <i class="fas fa-chevron-right text-[8px] mx-2 text-slate-300"></i>
        <span class="text-dark truncate">{{$product->name}}</span>
    </nav>

    <div class="mat-card mat-elevated border-0 overflow-hidden mt-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-0">
            
            {{-- Gallery --}}
            <div class="md:col-span-5 p-4 sm:p-6 border-b md:border-b-0 md:border-r border-slate-100">
                {{-- Main Image --}}
                <div class="aspect-square bg-slate-50 rounded-xl flex items-center justify-center p-6 relative overflow-hidden group mb-4">
                    <img :src="mainImg" class="max-w-full max-h-full object-contain transition-transform duration-500 group-hover:scale-110" alt="{{$product->name}}">
                    @if($product->sale_price)
                        @php $discount = round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100); @endphp
                        <span class="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-2.5 py-1 rounded-md">-{{$discount}}%</span>
                    @endif
                </div>
                
                {{-- Thumbnails --}}
                @if(!empty($product->gallery))
                <div class="flex gap-2 overflow-x-auto hide-scroll">
                    <button @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                        class="w-16 h-16 rounded-lg border-2 p-1 shrink-0 flex items-center justify-center transition"
                        :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary' : 'border-slate-200'">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain">
                    </button>
                    @foreach($product->gallery as $img)
                    <button @click="mainImg = '{{asset('storage/'.$img)}}'" 
                        class="w-16 h-16 rounded-lg border-2 p-1 shrink-0 flex items-center justify-center transition"
                        :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-primary' : 'border-slate-200'">
                        <img src="{{asset('storage/'.$img)}}" class="max-w-full max-h-full object-contain" loading="lazy">
                    </button>
                    @endforeach
                </div>
                @endif
                
                {{-- Video Player --}}
                @if($product->video_url)
                <div class="mt-4">
                    <h4 class="text-xs sm:text-sm font-bold text-dark mb-2 flex items-center gap-1.5"><i class="fas fa-play-circle text-primary"></i> প্রোডাক্ট ভিডিও</h4>
                    <div class="w-full aspect-video rounded-xl bg-slate-50 border border-slate-100 overflow-hidden relative">
                        @php
                            $videoEmbed = $product->video_url;
                            if(str_contains($videoEmbed, 'youtu.be/')) $videoEmbed = str_replace('youtu.be/', 'www.youtube.com/embed/', $videoEmbed);
                            elseif(str_contains($videoEmbed, 'watch?v=')) $videoEmbed = str_replace('watch?v=', 'embed/', $videoEmbed);
                        @endphp
                        <iframe class="absolute inset-0 w-full h-full" src="{{ $videoEmbed }}" title="Product Video" frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
                @endif
            </div>
            
            {{-- Product Details --}}
            <div class="md:col-span-7 p-4 sm:p-6 md:p-8 flex flex-col">
                <h1 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-dark leading-snug mb-3">{{$product->name}}</h1>
                
                {{-- Rating --}}
                @if($client->widget('show_reviews'))
                <div class="flex items-center gap-3 mb-4 pb-4 border-b border-slate-100">
                    <div class="flex text-amber-400 text-sm">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= floor($avgRating)) <i class="fas fa-star"></i>
                            @elseif($i - $avgRating < 1 && $avgRating > 0) <i class="fas fa-star-half-alt"></i>
                            @else <i class="far fa-star text-slate-200"></i>
                            @endif
                        @endfor
                    </div>
                    @if($totalReviews > 0)
                        <span class="text-sm text-slate-500 font-medium">{{$totalReviews}}টি রিভিউ</span>
                    @else
                        <span class="text-sm text-slate-400">এখনো কোনো রিভিউ নেই</span>
                    @endif
                    <span class="text-slate-300">|</span>
                    @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                        <span class="text-red-500 text-sm font-bold"><i class="fas fa-circle text-[6px] mr-1"></i>স্টক শেষ</span>
                    @else
                        <span class="text-emerald-500 text-sm font-bold"><i class="fas fa-circle text-[6px] mr-1"></i>স্টকে আছে</span>
                    @endif
                </div>
                @endif

                {{-- Price --}}
                <div class="flex items-end gap-3 mb-6">
                    <span class="text-3xl sm:text-4xl font-extrabold text-primary tracking-tight">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                    @if($product->sale_price)
                        <del class="text-lg text-slate-400 font-semibold mb-1">৳{{number_format($product->regular_price)}}</del>
                        <span class="bg-red-50 text-red-500 text-xs font-bold px-2 py-1 rounded mb-1">
                            ৳{{number_format($product->regular_price - $product->sale_price)}} সেভ
                        </span>
                    @endif
                </div>

                <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-5 flex-1 flex flex-col">
                    {{-- Color Variation --}}
                    @if($product->colors)
                    <div>
                        <span class="text-xs font-bold text-dark block mb-3 uppercase tracking-wider">কালার: <span class="text-primary normal-case" x-text="color"></span></span>
                        <div class="flex gap-2 flex-wrap">
                            @foreach($product->colors as $c)
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden">
                                <span class="block px-5 py-2 rounded-full border-2 border-transparent bg-slate-100 text-sm font-bold peer-checked:bg-primary peer-checked:text-white peer-checked:shadow-md peer-checked:-translate-y-1 smooth-transition shadow-sm hover:bg-slate-200">{{$c}}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    {{-- Size Variation --}}
                    @if($product->sizes)
                    <div>
                        <span class="text-xs font-bold text-dark block mb-3 uppercase tracking-wider">সাইজ: <span class="text-primary normal-case" x-text="size"></span></span>
                        <div class="flex gap-2 flex-wrap">
                            @foreach($product->sizes as $s)
                            <label class="cursor-pointer">
                                <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden">
                                <span class="block min-w-12 h-10 px-3 flex items-center justify-center rounded-2xl border-2 border-transparent bg-slate-100 text-sm font-bold peer-checked:bg-slate-800 peer-checked:text-white peer-checked:shadow-md peer-checked:-translate-y-1 smooth-transition shadow-sm hover:bg-slate-200">{{$s}}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Quantity --}}
                    <div>
                        <span class="text-xs font-bold text-dark block mb-3 uppercase tracking-wider">পরিমাণ</span>
                        <div class="flex items-center border border-slate-200 rounded-lg w-fit">
                            <button type="button" @click="if(qty>1)qty--" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-dark hover:bg-slate-50 transition rounded-l-lg"><i class="fas fa-minus text-xs"></i></button>
                            <input type="number" name="qty" x-model="qty" class="w-14 text-center bg-transparent border-x border-slate-200 font-bold text-dark p-0 h-10 focus:ring-0 text-base" readonly>
                            <button type="button" @click="qty++" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-dark hover:bg-slate-50 transition rounded-r-lg"><i class="fas fa-plus text-xs"></i></button>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-3 pt-4 mt-auto">
                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <button type="button" disabled class="flex-1 py-3.5 bg-slate-100 text-slate-400 rounded-lg font-bold text-sm uppercase cursor-not-allowed">স্টক শেষ</button>
                        @else
                            @if($client->show_order_button ?? true)
                            <button type="submit" class="flex-1 py-4 bg-primary text-white rounded-xl font-bold text-base uppercase tracking-wider hover:bg-primary/90 smooth-transition shadow-[0_8px_15px_-3px_rgba(0,0,0,0.1)] hover:-translate-y-1 hover:shadow-[0_12px_20px_-5px_rgba(0,0,0,0.2)] flex items-center justify-center gap-2">
                                <i class="fas fa-shopping-cart"></i> এখনই কিনুন
                            </button>
                            @endif
                            
                            @include('shop.partials.chat-button', ['client' => $client])
                        @endif
                    </div>
                </form>
                
                {{-- Delivery Info --}}
                @if($client->widget('show_trust_badges'))
                <div class="mt-6 pt-5 border-t border-slate-100 grid grid-cols-2 gap-3">
                    <div class="flex items-center gap-2.5 text-slate-500">
                        <i class="fas fa-truck text-primary text-base"></i>
                        <div><span class="text-xs font-bold text-dark block">ঢাকায় ৳{{$client->delivery_charge_inside ?? 60}}</span><span class="text-[10px]">ঢাকার বাইরে ৳{{$client->delivery_charge_outside ?? 120}}</span></div>
                    </div>
                    <div class="flex items-center gap-2.5 text-slate-500">
                        <i class="fas fa-undo text-primary text-base"></i>
                        <div><span class="text-xs font-bold text-dark block">ইজি রিটার্ন</span><span class="text-[10px]">৭ দিনের মধ্যে</span></div>
                    </div>
                    <div class="flex items-center gap-2.5 text-slate-500">
                        <i class="fas fa-shield-check text-primary text-base"></i>
                        <div><span class="text-xs font-bold text-dark block">১০০% অরিজিনাল</span><span class="text-[10px]">গুণগত মান নিশ্চিত</span></div>
                    </div>
                    <div class="flex items-center gap-2.5 text-slate-500">
                        <i class="fas fa-money-bill-wave text-primary text-base"></i>
                        <div><span class="text-xs font-bold text-dark block">ক্যাশ অন ডেলিভারি</span><span class="text-[10px]">পণ্য হাতে পেয়ে পেমেন্ট</span></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Description & Key Features --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-8 sm:mt-10 mb-8 max-w-[1280px] mx-auto px-4">
        <div class="lg:col-span-8">
            <div class="mat-card mat-elevated border-0 p-6 sm:p-8">
                <h2 class="text-xl font-extrabold text-dark mb-6 flex items-center gap-3 pb-3 border-b border-slate-100">
                    <i class="fas fa-info-circle text-primary text-2xl"></i> পণ্যের বিবরণ
                </h2>
                <div class="prose prose-sm xl:prose-base max-w-none text-slate-600 leading-relaxed font-medium">
                    {!! clean($product->description ?? $product->long_description) !!}
                </div>
            </div>
        </div>
        
        @if($product->key_features)
        <div class="lg:col-span-4">
            <div class="mat-card mat-elevated border-0 p-6 sm:p-8 h-fit lg:sticky lg:top-40">
                <h2 class="text-xl font-extrabold text-dark mb-6 flex items-center gap-3 pb-3 border-b border-slate-100">
                    <i class="fas fa-check-circle text-emerald-500 text-2xl"></i> মূল বৈশিষ্ট্য
                </h2>
                <ul class="space-y-4">
                    @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                        <li class="flex items-start gap-3 bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <i class="fas fa-check text-primary text-sm mt-0.5 shrink-0 bg-white shadow-sm w-6 h-6 flex items-center justify-center rounded-full"></i>
                            <span class="text-sm text-slate-700 font-bold">{{$feature}}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    {{-- Reviews --}}
    @if($client->widget('show_reviews'))
        @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
    @endif


        @include('shop.partials.related-products', ['client' => $client, 'product' => $product])
    @include('shop.partials.product-warranty', ['client' => $client, 'product' => $product])
</main>
@endsection
