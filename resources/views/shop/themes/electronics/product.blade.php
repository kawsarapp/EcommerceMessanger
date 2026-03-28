@extends('shop.themes.electronics.layout')
@section('title', $product->name . ' | Spec Details')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-[100rem] mx-auto px-4 md:px-8 py-10" x-data="{ mainImg: '{{asset('storage/'.$product->thumbnail)}}', qty: 1, color: '', size: '' }">
    
    <!-- Breadcrumb terminal style -->
    <div class="mb-8 font-mono text-[10px] font-bold text-gray-500 tracking-widest uppercase flex items-center gap-2">
        <a href="{{$baseUrl}}" class="hover:text-primary transition">Root</a> 
        <span class="text-gray-700">/</span> 
        <span class="text-gray-400 truncate">{{$product->category->name ?? 'Hardware'}}</span>
        <span class="text-gray-700">/</span> 
        <span class="text-white truncate max-w-[200px]">{{$product->name}}</span>
    </div>

    <div class="hud-panel neon-border rounded-2xl p-4 md:p-8 lg:p-10 mb-10 shadow-[0_0_20px_rgba(14,165,233,0.1)]">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">
            
            <!-- Left: Imagery Gallery -->
            <div class="flex flex-col space-y-4">
                <div class="w-full aspect-square bg-[#0a0f18] rounded-xl relative p-8 flex items-center justify-center border border-primary/20 overflow-hidden">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMGg0MHY0MEgweiIgZmlsbD0ibm9uZSIvPjxwYXRoIGQ9Ik0wIDM5LjVoNDBWNDBIMHptMzkuNSAwdjQwSDQwdi00MHoiIGZpbGw9InJnYmEoMjU1LCAyNTUsIDI1NSwgMC4wMikiLz48L3N2Zz4=')] opacity-50 z-0 pointer-events-none"></div>
                    <img :src="mainImg" class="max-w-full max-h-full object-contain mix-blend-multiply drop-shadow-xl transition-transform duration-500 scale-100 hover:scale-110">
                    
                    @if($product->sale_price)
                        <div class="absolute top-4 left-4 bg-red-500 text-white font-mono font-black text-xs px-3 py-1.5 rounded uppercase shadow-[0_0_15px_rgba(239,68,68,0.4)]">
                            Discount Active
                        </div>
                    @endif
                </div>
                
                <div class="flex gap-3 overflow-x-auto hide-scroll pb-2 mt-4 relative z-10">
                    <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" class="w-20 aspect-square bg-[#0a0f18] rounded-lg p-2 flex items-center justify-center border transition-all shrink-0 hover:shadow-[0_0_10px_var(--tw-color-primary)]" :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary neon-border' : 'border-gray-800 opacity-60 hover:opacity-100 hover:border-primary/50'">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain">
                    </button>
                    @foreach($product->gallery ?? [] as $img)
                    <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" class="w-20 aspect-square bg-[#0a0f18] rounded-lg p-2 flex items-center justify-center border transition-all shrink-0 hover:shadow-[0_0_10px_var(--tw-color-primary)]" :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-primary neon-border' : 'border-gray-800 opacity-60 hover:opacity-100 hover:border-primary/50'">
                        <img src="{{asset('storage/'.$img)}}" class="max-w-full max-h-full object-contain">
                    </button>
                    @endforeach
                </div>
            </div>
            
            <!-- Right: Specs & Configuration -->
            <div class="flex flex-col">
                <div class="border-b border-gray-800 pb-8 mb-8">
                    @if(isset($product->stock_status))
                        @if($product->stock_status == 'out_of_stock')
                            <div class="inline-flex items-center gap-2 bg-red-500/10 border border-red-500/20 text-red-500 text-[10px] font-bold font-mono px-3 py-1.5 rounded mb-4 uppercase tracking-widest">
                                <i class="fas fa-times-circle"></i> Out of Stock
                            </div>
                        @else
                            <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold font-mono px-3 py-1.5 rounded mb-4 uppercase tracking-widest">
                                <i class="fas fa-check-circle"></i> In Stock
                            </div>
                        @endif
                    @endif

                    <h1 class="text-3xl lg:text-4xl font-black text-white leading-tight mb-4 tracking-tight">{{$product->name}}</h1>
                    
                    <div class="flex flex-col sm:flex-row sm:items-end gap-3 sm:gap-6 bg-dark/50 tech-border rounded-xl p-6 relative overflow-hidden">
                        <div class="absolute right-0 top-0 w-32 h-32 bg-primary/10 rounded-full blur-3xl point-events-none"></div>
                        <div>
                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest block mb-1">MSRP</span>
                            <span class="text-4xl font-black font-mono text-white tracking-tighter">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                        </div>
                        @if($product->sale_price)
                            <div class="pb-1">
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest block mb-1">Original List</span>
                                <del class="text-lg font-mono text-line-through text-red-400 font-bold">৳{{number_format($product->regular_price)}}</del>
                            </div>
                        @endif
                    </div>
                </div>

                <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-8 flex-1 flex flex-col">
                    
                    <div class="space-y-6 flex-1">
                        @if($product->colors)
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Configuration Color</span>
                                <span class="text-primary font-mono text-sm font-bold" x-text="color"></span>
                            </div>
                            <div class="flex gap-3 flex-wrap">
                                @foreach($product->colors as $c)
                                <label class="cursor-pointer group">
                                    <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer sr-only" required>
                                    <span class="block px-5 py-2.5 rounded border border-gray-700 bg-dark text-gray-400 font-bold font-mono text-sm transition-all peer-checked:bg-primary/20 peer-checked:neon-border peer-checked:text-primary hover:border-primary hover:shadow-[0_0_10px_var(--tw-color-primary)]">{{$c}}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if($product->sizes)
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Memory / Size Option</span>
                                <span class="text-primary font-mono text-sm font-bold" x-text="size"></span>
                            </div>
                            <div class="flex gap-3 flex-wrap">
                                @foreach($product->sizes as $s)
                                <label class="cursor-pointer group">
                                    <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer sr-only" required>
                                    <span class="block px-5 py-2.5 rounded border border-gray-700 bg-dark text-gray-400 font-bold font-mono text-sm transition-all peer-checked:bg-primary/20 peer-checked:neon-border peer-checked:text-primary hover:border-primary hover:shadow-[0_0_10px_var(--tw-color-primary)]">{{$s}}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="flex gap-4 pt-6 border-t border-gray-800">
                        <div class="w-32 bg-dark tech-border rounded-xl flex border border-gray-700">
                            <button type="button" @click="if(qty>1)qty--" class="flex-1 text-gray-400 hover:text-white transition"><i class="fas fa-minus text-xs"></i></button>
                            <input type="number" name="qty" x-model="qty" class="w-12 text-center bg-transparent border-none font-mono font-bold text-white p-0 focus:ring-0" readonly>
                            <button type="button" @click="qty++" class="flex-1 text-gray-400 hover:text-white transition"><i class="fas fa-plus text-xs"></i></button>
                        </div>
                        
                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <button type="button" disabled class="flex-1 bg-gray-900 border border-gray-800 text-gray-600 rounded-none font-bold font-mono uppercase tracking-widest text-sm cursor-not-allowed">SYS_OFFLINE</button>
                        @else
                            <button type="submit" class="flex-1 bg-primary/10 text-primary rounded-none font-bold neon-border transition-all hover:bg-primary hover:text-dark uppercase tracking-widest text-sm flex items-center justify-center gap-2 hover:shadow-[0_0_20px_var(--tw-color-primary)] group/btn relative overflow-hidden">
                                <span class="absolute inset-0 w-full h-full bg-primary/20 -translate-x-full group-hover/btn:animate-[shimmer_1s_infinite]"></span>
                                <i class="fas fa-terminal"></i> [ INIT CHECKOUT ]
                            </button>
                        @endif
                    </div>
                </form>

            </div>
        </div>
    </div>
    
    <!-- Specs Details section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        <div class="lg:col-span-8 bg-panel tech-border rounded-2xl p-6 md:p-10">
            <h2 class="text-xl font-bold text-white mb-6 border-b border-gray-800 pb-4 flex items-center gap-2"><i class="fas fa-info-circle text-primary"></i> Technical Details</h2>
            <div class="prose prose-invert prose-p:text-gray-400 prose-headings:text-gray-200 max-w-none text-sm font-medium leading-relaxed">
                {!! clean($product->description ?? $product->long_description) !!}
            </div>
        </div>
        
        @if($product->key_features)
        <div class="lg:col-span-4 bg-dark tech-border rounded-2xl p-6 md:p-8 self-start sticky top-28">
            <h2 class="text-lg font-bold text-white mb-6 border-b border-gray-800 pb-4 font-mono tracking-wide uppercase"><i class="fas fa-cogs text-primary"></i> Specs Sheet</h2>
            <ul class="space-y-4">
                @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                    <li class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-primary mt-1 text-sm bg-primary/20 rounded-full"></i>
                        <span class="text-sm font-medium text-gray-300">{{$feature}}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

    </div>


        @include('shop.partials.related-products', ['client' => $client, 'product' => $product])
    @include('shop.partials.product-warranty', ['client' => $client, 'product' => $product])
</main>

    {{-- Dynamic Reviews Section --}}
    @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])

@endsection