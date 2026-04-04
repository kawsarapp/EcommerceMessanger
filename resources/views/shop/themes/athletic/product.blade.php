@extends('shop.themes.athletic.layout')
@section('title', $product->meta_title ?? (strtoupper($product->name) . ' | ' . $client->shop_name))

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-[100rem] mx-auto px-4 sm:px-8 py-16" x-data="{ 
    mainImg: '{{asset('storage/'.$product->thumbnail)}}', 
    qty: 1, 
    color: '', 
    size: '', 
    show: false,
    hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
    variants: {{ $product->has_variants ? $product->variants->toJson() : '[]' }},
    basePrice: {{ $product->sale_price ?? $product->regular_price ?? 0 }},
    currentPrice: {{ $product->sale_price ?? $product->regular_price ?? 0 }},
    updatePrice() {
        if(this.hasVariants) {
            let matched = this.variants.find(v => 
                (v.color === this.color || (!v.color && !this.color)) && 
                (v.size === this.size || (!v.size && !this.size))
            );
            if(matched && matched.price) {
                this.currentPrice = parseInt(matched.price);
            } else {
                this.currentPrice = this.basePrice;
            }
        }
    }
}" x-init="setTimeout(() => show = true, 50); $watch('color', () => updatePrice()); $watch('size', () => updatePrice());">
    
    <!-- Breadcrumb -->
    <div class="mb-10 flex gap-4 uppercase font-display font-bold text-2xl tracking-widest text-dark overflow-x-auto hide-scroll border-b-4 border-dark pb-3">
        <a href="{{$baseUrl}}" class="hover:text-primary transition-colors flex items-center gap-2">
            <i class="fas fa-home text-lg"></i> হোম
        </a>
        <span class="text-primary italic">//</span>
        <span class="text-gray-400">{{$product->category->name ?? 'পণ্য'}}</span>
        <span class="text-primary italic">//</span>
        <span class="truncate">{{$product->name}}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-20 items-stretch">
        
        <!-- Left Column: Product Images (7/12) -->
        <div class="lg:col-span-7 flex flex-col font-sans transition-all duration-[600ms] ease-out delay-100" :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'">
            <div class="w-full aspect-square md:aspect-[4/5] bg-gray-50 card-brutal relative group overflow-hidden mb-6 filter contrast-[1.05]">
                <img :src="mainImg" class="w-full h-full object-cover mix-blend-multiply cursor-crosshair transform group-hover:scale-125 transition-transform duration-[2s] ease-in-out" loading="lazy" alt="{{$product->name}}">
            </div>
            
            <!-- Image Thumbnails -->
            <div class="flex gap-4 overflow-x-auto hide-scroll pb-4 -skew-x-[4deg]">
                <div @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                     class="w-24 h-32 md:w-32 md:h-40 shrink-0 border-4 cursor-pointer transition-all skew-x-[4deg]"
                     :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary shadow-primary-sm' : 'border-dark opacity-60 hover:opacity-100'">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-cover mix-blend-multiply" alt="{{$product->name}}">
                </div>
                
                @foreach($product->gallery ?? [] as $img)
                <div @click="mainImg = '{{asset('storage/'.$img)}}'" 
                     class="w-24 h-32 md:w-32 md:h-40 shrink-0 border-4 cursor-pointer transition-all skew-x-[4deg]"
                     :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-primary shadow-primary-sm' : 'border-dark opacity-60 hover:opacity-100'">
                    <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-cover mix-blend-multiply" loading="lazy" alt="{{$product->name}}">
                </div>
                @endforeach
            </div>

            @if($product->video_url)
            <a href="{{$product->video_url}}" target="_blank" class="w-full mt-4 btn-speed bg-red-600 text-white font-display font-bold text-2xl uppercase tracking-widest text-center py-4 border-4 border-dark shadow-dark-md">
                <span><i class="fab fa-youtube mr-3"></i> ভিডিও দেখুন</span>
            </a>
            @endif
        </div>
        
        <!-- Right Column: Product Details (5/12) -->
        <div class="lg:col-span-5 flex flex-col transition-all duration-[600ms] ease-out delay-200" :class="show ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-16'">
            
            <!-- Product Title -->
            <div class="mb-10">
                @if($product->brand)
                <div class="font-display font-bold text-2xl text-primary tracking-widest uppercase mb-2 skew-x-[4deg]">
                    <i class="fas fa-tag"></i> {{$product->brand}}
                </div>
                @endif
                <h1 class="text-6xl md:text-8xl lg:text-[7rem] font-display font-bold uppercase tracking-tighter leading-[0.85] text-dark mix-blend-multiply relative z-10">{{$product->name}}</h1>
                
                <div class="w-1/2 h-4 bg-primary -mt-6 relative z-0 -skew-x-[20deg] opacity-70"></div>
                
                <!-- Reviews -->
                @php 
                    $reviews = $product->reviews()->where('is_visible', true)->get();
                    $rc = $reviews->count();
                    $avg = $rc > 0 ? round($reviews->avg('rating'), 1) : 0;
                @endphp
                @if($rc > 0)
                <div class="flex items-center gap-3 mt-6 bg-dark text-white px-4 py-2 w-fit -skew-x-[8deg]">
                    <div class="text-primary skew-x-[8deg]">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star text-sm {{ $i <= round($avg) ? 'text-primary' : 'text-gray-600' }}"></i>
                        @endfor
                    </div>
                    <span class="font-display font-bold text-lg uppercase tracking-widest skew-x-[8deg]">{{$rc}} রিভিউ ({{$avg}}/5)</span>
                </div>
                @endif
            </div>

            <!-- Pricing -->
            <div class="flex flex-col bg-gray-100 border-l-[12px] border-dark px-8 py-6 mb-12 relative overflow-hidden">
                <div class="absolute inset-0 bg-primary opacity-5 transform skew-x-[45deg] scale-150"></div>
                <div class="flex items-end gap-6 relative z-10">
                    <span class="font-display font-bold text-6xl tracking-tighter leading-none text-dark" x-text="'৳' + new Intl.NumberFormat('en-IN').format(currentPrice)">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                    @if($product->sale_price)
                        <del class="font-display font-bold text-3xl text-primary opacity-60 decoration-[4px] underline-offset-4 decoration-dark leading-none">৳{{number_format($product->regular_price)}}</del>
                        @php $saving = $product->regular_price - $product->sale_price; $savePct = round(($saving/$product->regular_price)*100); @endphp
                        <span class="bg-primary text-white font-display font-bold text-xl px-3 py-1 -skew-x-[8deg] shadow-dark-sm">
                            <span class="block skew-x-[8deg]">{{$savePct}}% ছাড়</span>
                        </span>
                    @endif
                </div>
                @if($product->sale_price)
                <p class="text-sm font-sans text-green-700 font-bold mt-2 relative z-10">আপনি সাশ্রয় করছেন ৳{{number_format($saving)}}</p>
                @endif
            </div>

            <!-- Order Form -->
            <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="border-y-8 border-dark py-12 mb-12 space-y-10">
                
                @if($product->colors)
                <div>
                    <span class="font-display font-bold text-2xl uppercase tracking-widest block mb-4 border-l-4 border-primary pl-3">রঙ বেছে নিন</span>
                    <div class="flex gap-4 flex-wrap">
                        @foreach($product->colors as $c)
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden">
                            <span class="btn-speed bg-gray-200 text-dark border-2 border-transparent peer-checked:bg-primary peer-checked:text-white peer-checked:border-dark peer-checked:shadow-primary-md px-8 py-4 transition-all">
                                <span>{{$c}}</span>
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif
                
                @if($product->sizes)
                <div>
                    <span class="font-display font-bold text-2xl uppercase tracking-widest block mb-4 border-l-4 border-primary pl-3">সাইজ বেছে নিন</span>
                    <div class="flex gap-4 flex-wrap">
                        @foreach($product->sizes as $s)
                        <label class="cursor-pointer">
                            <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden">
                            <span class="btn-speed bg-gray-200 text-dark border-2 border-transparent peer-checked:bg-primary peer-checked:text-white peer-checked:border-dark peer-checked:shadow-primary-md w-16 h-16 flex items-center justify-center transition-all">
                                <span>{{$s}}</span>
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(($client->show_stock ?? true) && (!isset($product->stock_status) || $product->stock_status != 'out_of_stock'))
                    <div class="font-display font-bold text-2xl text-green-600 uppercase tracking-widest -skew-x-[4deg] bg-green-50 border-4 border-green-600 px-6 py-3 w-fit shadow-dark-sm">
                        <span class="skew-x-[4deg]"><i class="fas fa-check-square mr-2"></i> স্টকে আছে — এখনই অর্ডার করুন</span>
                    </div>
                @endif

                <!-- Quantity & Order Button -->
                <div class="flex flex-col xl:flex-row gap-6 pt-4">
                    <div class="flex border-4 border-dark h-20 w-full xl:w-1/3 shrink-0 bg-white -skew-x-[6deg]">
                        <button type="button" @click="if(qty>1)qty--" class="flex-1 text-dark hover:bg-gray-100 flex items-center justify-center font-display font-bold text-3xl skew-x-[6deg]"><i class="fas fa-minus text-xl"></i></button>
                        <input type="number" name="qty" x-model="qty" class="w-16 text-center font-display font-bold text-4xl p-0 focus:ring-0 border-x-4 border-dark skew-x-[6deg]" readonly>
                        <button type="button" @click="qty++" class="flex-1 text-dark hover:bg-gray-100 flex items-center justify-center font-display font-bold text-3xl skew-x-[6deg]"><i class="fas fa-plus text-xl"></i></button>
                    </div>
                    
                    @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                        <button type="button" disabled class="h-20 w-full xl:w-2/3 bg-dark text-white font-display font-bold text-3xl uppercase tracking-widest opacity-50 cursor-not-allowed border-4 border-dark flex justify-center items-center">
                            <span>স্টক শেষ</span>
                        </button>
                    @else
                        @if($client->show_order_button ?? true)
                            <button type="submit" class="h-20 w-full xl:w-2/3 btn-speed shadow-primary-lg border-4 border-dark flex justify-center items-center">
                                <span class="font-display font-bold text-3xl uppercase tracking-widest">অর্ডার করুন <i class="fas fa-bolt ml-3"></i></span>
                            </button>
                        @endif

                        @if($client->fb_page_id)
                        <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="h-20 bg-blue-600 text-white font-display font-bold text-3xl uppercase tracking-widest border-4 border-dark -skew-x-[6deg] flex items-center justify-center px-8 shadow-dark-lg hover:bg-blue-700 transition xl:hidden">
                            <span class="skew-x-[6deg]"><i class="fab fa-facebook-messenger mr-3"></i> Messenger-এ যোগাযোগ</span>
                        </a>
                        @endif
                    @endif
                </div>

                @if($client->delivery_charge_inside ?? false)
                <div class="bg-gray-100 py-4 px-6 font-display font-bold text-xl text-dark flex items-center justify-center gap-4 uppercase tracking-widest -skew-x-[4deg]">
                    <span class="skew-x-[4deg]"><i class="fas fa-truck-fast text-primary mr-2"></i> ঢাকায় ৳{{$client->delivery_charge_inside}} | ঢাকার বাইরে ৳{{$client->delivery_charge_outside ?? 120}}</span>
                </div>
                @else
                <div class="bg-gray-100 py-4 px-6 font-display font-bold text-xl text-dark flex items-center justify-center gap-4 uppercase tracking-widest -skew-x-[4deg]">
                    <span class="skew-x-[4deg]"><i class="fas fa-truck-fast text-primary mr-2"></i> দ্রুত ডেলিভারি দেওয়া হয়।</span>
                </div>
                @endif
            </form>
            
            <!-- Product Description -->
            @if($product->description ?? $product->short_description)
            <div class="pt-8 mb-12 max-w-none prose prose-lg prose-headings:font-display prose-headings:font-bold prose-headings:uppercase text-gray-800 font-sans leading-relaxed">
                <h2 class="text-4xl border-b-8 border-dark pb-4 uppercase">পণ্যের বিবরণ</h2>
                {!! clean($product->description ?? $product->short_description) !!}
            </div>
            @endif

            <!-- Specs & Policies -->
            @if($product->key_features || $product->material || ($client->show_return_warranty ?? true))
            <div class="bg-dark text-white p-8 md:p-12 -skew-x-[4deg] shadow-primary-xl">
                <h3 class="font-display text-4xl mb-6 uppercase tracking-widest border-b-2 border-primary pb-4 inline-block skew-x-[4deg]">স্পেসিফিকেশন</h3>
                
                <div class="grid md:grid-cols-2 gap-8 skew-x-[4deg]">
                    <div>
                        @if($product->key_features)
                        <ul class="space-y-3 font-sans font-bold text-sm">
                            @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                                <li class="flex items-start"><i class="fas fa-square text-primary mt-1.5 mr-4 text-xs"></i> {{$feature}}</li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                    
                    <div class="space-y-4 font-sans font-bold text-sm bg-black/30 p-6 border-l-4 border-primary">
                        @if($product->material)
                        <div class="flex items-center gap-3">
                            <i class="fas fa-cube text-primary w-5"></i>
                            <div>
                                <div class="text-xs text-gray-400 uppercase tracking-wider">উপাদান</div>
                                <div>{{$product->material}}</div>
                            </div>
                        </div>
                        @endif

                        @if($product->weight ?? false)
                        <div class="flex items-center gap-3">
                            <i class="fas fa-weight text-primary w-5"></i>
                            <div>
                                <div class="text-xs text-gray-400 uppercase tracking-wider">ওজন</div>
                                <div>{{$product->weight}}</div>
                            </div>
                        </div>
                        @endif

                        @if($client->show_return_warranty ?? true)
                        @if($product->warranty ?? false)
                        <div class="flex items-center gap-3">
                            <i class="fas fa-shield-alt text-primary w-5"></i>
                            <div>
                                <div class="text-xs text-gray-400 uppercase tracking-wider">ওয়ারেন্টি</div>
                                <div>{{$product->warranty}}</div>
                            </div>
                        </div>
                        @endif

                        <div class="flex items-center gap-3">
                            <i class="fas fa-undo-alt text-primary w-5"></i>
                            <div>
                                <div class="text-xs text-gray-400 uppercase tracking-wider">রিটার্ন পলিসি</div>
                                <div>{{$product->return_policy ?? '৭ দিনের মধ্যে রিটার্ন করা যাবে'}}</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    @include('shop.partials.related-products', ['client' => $client, 'product' => $product])
</main>

@include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])

@endsection
