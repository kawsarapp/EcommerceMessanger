@extends('shop.themes.default.layout')
@section('title', $product->name . ' | Shop')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : route('shop.show', $client->slug); 
@endphp

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-12" x-data="{ 
    mainImg: '{{asset('storage/'.$product->thumbnail)}}', 
    qty: 1, 
    color: '', 
    size: '', 
    activeTab: 'description',
    hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
    variants: {{ $product->has_variants ? $product->variants->toJson() : '[]' }},
    basePrice: {{ $product->sale_price ?? $product->regular_price ?? 0 }},
    currentPrice: {{ $product->sale_price ?? $product->regular_price ?? 0 }},
    currentVariant: null,
    updatePrice() {
        if(this.hasVariants) {
            let matched = this.variants.find(v => 
                (v.color === this.color || (!v.color && !this.color)) && 
                (v.size === this.size || (!v.size && !this.size))
            );
            if(matched) {
                this.currentVariant = matched;
                this.currentPrice = parseInt(matched.price || this.basePrice);
                if(matched.image) {
                    this.mainImg = '/storage/' + matched.image;
                }
            } else {
                this.currentVariant = null;
                this.currentPrice = this.basePrice;
            }
        }
    }
}" x-init="$watch('color', () => updatePrice()); $watch('size', () => updatePrice()); updatePrice();">
    
    <!-- Clean Breadcrumb -->
    <nav class="mb-8 flex items-center text-xs font-bold uppercase tracking-wider text-slate-500 overflow-hidden bg-white/40 backdrop-blur-xl px-5 py-3.5 rounded-2xl w-fit border border-white shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <a href="{{$baseUrl}}" class="hover:text-primary premium-transition">Home</a>
        <i class="fas fa-chevron-right text-[10px] mx-3 text-slate-300"></i>
        <a href="{{$baseUrl}}?category={{$product->category->slug ?? ''}}" class="hover:text-primary premium-transition cursor-pointer">{{$product->category->name ?? 'Catalog'}}</a>
        <i class="fas fa-chevron-right text-[10px] mx-3 text-slate-300"></i>
        <span class="text-slate-900 truncate">{{$product->name}}</span>
    </nav>

    <div class="glass-panel rounded-[2.5rem] p-6 sm:p-10 lg:p-12 mb-16 relative group/product">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-primary/10 rounded-full blur-[100px] pointer-events-none premium-transition group-hover/product:scale-110"></div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 relative z-10">
            
            <!-- Gallery (Left) -->
            <div class="lg:col-span-5 flex flex-col space-y-6 lg:sticky lg:top-32 lg:h-fit">
                <!-- Main Focus Image -->
                <div class="w-full aspect-square bg-white/60 backdrop-blur-sm rounded-3xl relative p-8 flex items-center justify-center group overflow-hidden border border-white shadow-sm">
                    <img :src="mainImg" class="max-w-full max-h-full object-contain mix-blend-multiply premium-transition group-hover:scale-110 z-10 duration-[1.5s]" loading="lazy">
                    
                    @if($product->sale_price)
                        @php $discountPercent = round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100); @endphp
                        <div class="absolute top-5 left-5 z-20 bg-red-500 text-white font-bold text-xs uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-sm">
                            -{{ $discountPercent }}%
                        </div>
                    @endif
                </div>
                
                <!-- Gallery Thumbnails -->
                @if(!empty($product->gallery))
                <div class="flex gap-4 overflow-x-auto hide-scroll pb-2 px-1">
                    <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                        class="w-20 aspect-square rounded-2xl p-2 flex items-center justify-center transition-all shrink-0 bg-white" 
                        :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-2 border-primary shadow-sm ring-4 ring-primary/5' : 'border border-slate-200 hover:border-slate-300'">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain mix-blend-multiply">
                    </button>
                    @foreach($product->gallery as $img)
                    <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" 
                        class="w-20 aspect-square rounded-2xl p-2 flex items-center justify-center transition-all shrink-0 bg-white" 
                        :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-2 border-primary shadow-sm ring-4 ring-primary/5' : 'border border-slate-200 hover:border-slate-300'">
                        <img src="{{asset('storage/'.$img)}}" class="max-w-full max-h-full object-contain mix-blend-multiply" loading="lazy">
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
            

            <!-- Details & Actions (Right) -->
            <div class="lg:col-span-7 flex flex-col">
                <div class="mb-8">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 leading-[1.1] mb-4 tracking-tight">{{$product->name}}</h1>
                    
                    <div class="flex items-center flex-wrap gap-4 text-sm text-slate-500 font-semibold tracking-wide uppercase mb-6">
                        <span>SKU: <span class="text-slate-800">{{ $product->sku ?? 'PRD-'.$product->id }}</span></span>
                        <div class="w-1 h-1 bg-slate-300 rounded-full"></div>
                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <span class="text-red-500"><i class="fas fa-circle text-[8px] mr-1"></i> Out of Stock</span>
                        @else
                            <span class="text-emerald-500"><i class="fas fa-circle text-[8px] mr-1"></i> In Stock @if($client->show_stock ?? true)({{ $product->stock_quantity }})@endif</span>
                        @endif

                        {{-- Warranty & Return inline --}}
                        @if(($client->show_return_warranty ?? true) && !empty($product->warranty))
                            <div class="w-1 h-1 bg-slate-300 rounded-full"></div>
                            <span class="text-blue-500"><i class="fas fa-shield-alt text-[8px] mr-1"></i> {{ $product->warranty }}</span>
                        @endif
                        @if(($client->show_return_warranty ?? true) && !empty($product->return_policy))
                            <div class="w-1 h-1 bg-slate-300 rounded-full"></div>
                            <span class="text-orange-500"><i class="fas fa-undo text-[8px] mr-1"></i> {{ $product->return_policy }}</span>
                        @endif
                    </div>

                    @php
                        $reviews = $product->reviews()->where('is_visible', true)->get();
                        $avgRating = $reviews->avg('rating') ?? 0;
                        $totalReviews = $reviews->count();
                    @endphp
                    <div class="flex items-center gap-2">
                        <div class="flex text-amber-400">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($avgRating))
                                    <i class="fas fa-star"></i>
                                @elseif($i - $avgRating < 1 && $avgRating > 0)
                                    <i class="fas fa-star-half-alt"></i>
                                @else
                                    <i class="far fa-star text-slate-200"></i>
                                @endif
                            @endfor
                        </div>
                        @if($totalReviews > 0)
                            <span class="text-slate-400 text-sm ml-1 font-medium">({{ $totalReviews }} {{ $totalReviews > 1 ? 'Reviews' : 'Review' }})</span>
                        @else
                            <span class="text-slate-400 text-sm ml-1 font-medium">(No reviews yet)</span>
                        @endif
                    </div>

                    <div class="flex items-end gap-3 mt-6">
                        <span class="text-4xl font-extrabold text-slate-900 tracking-tight" x-text="'৳' + new Intl.NumberFormat('en-IN').format(currentPrice)">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
    @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])

                        @if($product->sale_price)
                            <del class="text-xl text-slate-400 font-semibold mb-1">৳{{number_format($product->regular_price)}}</del>
                            <span class="bg-red-50 text-red-500 text-xs font-bold px-2.5 py-1 rounded-lg mb-1">Save ৳{{ number_format($product->regular_price - $product->sale_price) }}</span>
                        @endif
                    </div>
                </div>

                @include('shop.partials.product-variations')

            </div>
        </div>
    </div>
    
    <!-- Info Section with Tabs (Bottom) -->
    <div class="glass-panel border-white rounded-[2.5rem] mb-16 overflow-hidden relative">
        <div class="absolute inset-0 bg-gradient-to-br from-white/40 to-white/10 pointer-events-none"></div>
        {{-- Tab Bar --}}
        <div class="flex border-b border-slate-100 overflow-x-auto hide-scroll">
            <button @click="activeTab = 'description'" 
                :class="activeTab === 'description' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="px-6 sm:px-8 py-5 font-bold text-sm uppercase tracking-wider border-b-2 transition-all whitespace-nowrap">
                <i class="fas fa-align-left mr-2"></i>Description
            </button>
            @if($product->key_features)
            <button @click="activeTab = 'features'" 
                :class="activeTab === 'features' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="px-6 sm:px-8 py-5 font-bold text-sm uppercase tracking-wider border-b-2 transition-all whitespace-nowrap">
                <i class="fas fa-list-check mr-2"></i>Key Features
            </button>
            @endif
            @if(($client->show_return_warranty ?? true) && (!empty($product->warranty) || !empty($product->return_policy)))
            <button @click="activeTab = 'warranty'" 
                :class="activeTab === 'warranty' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="px-6 sm:px-8 py-5 font-bold text-sm uppercase tracking-wider border-b-2 transition-all whitespace-nowrap">
                <i class="fas fa-shield-alt mr-2"></i>Warranty & Return
            </button>
            @endif
            @if($product->video_url)
            <button @click="activeTab = 'video'" 
                :class="activeTab === 'video' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="px-6 sm:px-8 py-5 font-bold text-sm uppercase tracking-wider border-b-2 transition-all whitespace-nowrap">
                <i class="fas fa-play-circle mr-2"></i>Video
            </button>
            @endif
            @if(isset($product->reviews) && $product->reviews()->where('is_visible', true)->count() > 0)
            <button @click="activeTab = 'reviews'" 
                :class="activeTab === 'reviews' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="px-6 sm:px-8 py-5 font-bold text-sm uppercase tracking-wider border-b-2 transition-all whitespace-nowrap">
                <i class="fas fa-star mr-2"></i>Reviews
            </button>
            @endif
        </div>
        
        {{-- Tab Content --}}
        <div class="p-8 md:p-12">
            {{-- Description --}}
            <div x-show="activeTab === 'description'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="prose prose-slate max-w-none font-medium text-slate-600 leading-relaxed prose-p:mb-5">
                    {!! clean($product->description ?? $product->long_description) !!}
                </div>
            </div>

            {{-- Key Features --}}
            @if($product->key_features)
            <div x-show="activeTab === 'features'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <ul class="space-y-4 max-w-2xl">
                    @foreach(is_string($product->key_features) ? json_decode($product->key_features, true) : $product->key_features as $feature)
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-check text-primary text-[10px]"></i></div>
                            <span class="text-sm font-semibold text-slate-700 leading-relaxed">{{$feature}}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Warranty & Return --}}
            @if(($client->show_return_warranty ?? true) && (!empty($product->warranty) || !empty($product->return_policy)))
            <div x-show="activeTab === 'warranty'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-2xl">
                    @if(!empty($product->warranty))
                    <div class="bg-blue-50 rounded-2xl p-6 border border-blue-100">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center"><i class="fas fa-shield-alt text-blue-500"></i></div>
                            <h4 class="font-bold text-slate-900">Warranty</h4>
                        </div>
                        <p class="text-sm text-slate-600 font-medium">{{ $product->warranty }}</p>
                    </div>
                    @endif
                    @if(!empty($product->return_policy))
                    <div class="bg-orange-50 rounded-2xl p-6 border border-orange-100">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center"><i class="fas fa-undo text-orange-500"></i></div>
                            <h4 class="font-bold text-slate-900">Return Policy</h4>
                        </div>
                        <p class="text-sm text-slate-600 font-medium">{{ $product->return_policy }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Video --}}
            @if($product->video_url)
            <div x-show="activeTab === 'video'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="max-w-4xl w-full aspect-video rounded-3xl overflow-hidden shadow-sm border border-slate-100 bg-slate-50">
                    @php
                        $videoEmbed = $product->video_url;
                        if(str_contains($videoEmbed, 'youtu.be/')) {
                            $videoEmbed = str_replace('youtu.be/', 'www.youtube.com/embed/', $videoEmbed);
                        } elseif (str_contains($videoEmbed, 'watch?v=')) {
                            $videoEmbed = str_replace('watch?v=', 'embed/', $videoEmbed);
                        }
                    @endphp
                    <iframe class="w-full h-full" src="{{ $videoEmbed }}" title="Product Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>
            @endif

            {{-- Reviews --}}
            @if(isset($product->reviews) && $product->reviews()->where('is_visible', true)->count() > 0)
            <div x-show="activeTab === 'reviews'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="-my-8 md:-my-12">
                    @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
                </div>
            </div>
            @endif
        </div>
    </div>


    {{-- Related Products --}}
    @include('shop.partials.related-products', ['client' => $client, 'product' => $product])

</main>
@include('shop.partials.product-sticky-bar')
@endsection
