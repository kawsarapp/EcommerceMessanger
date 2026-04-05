<?php
$file = 'resources/views/shop/themes/shoppers/layout.blade.php';
$content = file_get_contents($file);

$findCat = '            {{-- Category Button --}}
            <div class="w-64 h-full relative group">
                <a href="{{$baseUrl}}?category=all" class="h-full flex items-center justify-between px-5 bg-primary hover:bg-red-600 transition text-white text-sm font-bold cursor-pointer">
                    <div class="flex items-center gap-3 flex-1">
                        <span class="tracking-wide">ALL CATEGORIES</span>
                    </div>
                    <i class="fas fa-bars opacity-80"></i>
                </a>
            </div>';

$replaceCat = '            {{-- Category Button --}}
            <div class="relative group h-full flex items-center border border-gray-100 bg-gray-50 hover:bg-gray-100 transition px-5 w-64">
                @include(\'shop.partials.header-category-menu\')
            </div>';

$findCart = '                {{-- User / Cart Icons --}}
                <div class="hidden md:flex items-center gap-8 shrink-0 text-white">
                    <a href="#" class="flex items-center gap-3 hover:text-primary transition group cursor-pointer">
                        <div class="relative">
                            <i class="fas fa-shopping-cart text-2xl text-gray-300 group-hover:text-white transition"></i>
                            <span class="absolute -top-2 -right-2 bg-primary text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-gray-900">0</span>
                        </div>
                        <div class="flex flex-col pt-1">
                            <span class="text-[10px] text-gray-400 font-bold uppercase leading-none">0</span>
                            <span class="text-sm font-bold leading-none mt-1">My Cart</span>
                        </div>
                    </a>

                    <div class="flex items-center gap-3">
                        <i class="far fa-user text-2xl text-gray-300"></i>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-gray-300 uppercase leading-tight">Hello Guest!</span>
                            <span class="text-xs font-bold leading-tight mt-0.5"><a href="{{ $clean ? $baseUrl.\'/track\' : route(\'shop.track\', $client->slug) }}" class="hover:text-primary transition">Track Order</a></span>
                        </div>
                    </div>
                </div>';

$replaceCart = '                {{-- User / Cart Icons --}}
                <div class="hidden md:flex items-center gap-8 shrink-0 text-white">
                    @php $bgCartCount = session()->has(\'cart\') ? count(session()->get(\'cart\')) : 0; @endphp
                    <a href="{{$clean?$baseUrl.\'/cart\':route(\'shop.cart\',$client->slug)}}" class="flex items-center gap-3 hover:text-primary transition group cursor-pointer">
                        <div class="relative">
                            <i class="fas fa-shopping-cart text-2xl text-gray-300 group-hover:text-white transition"></i>
                            @if($bgCartCount > 0)
                                <span class="absolute -top-2 -right-2 bg-primary text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-gray-900" data-cart-badge>{{ $bgCartCount }}</span>
                            @else
                                <span class="absolute -top-2 -right-2 bg-primary text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-gray-900 hidden" data-cart-badge>0</span>
                            @endif
                        </div>
                        <div class="flex flex-col pt-1">
                            <span class="text-sm font-bold leading-none mt-1">My Cart</span>
                        </div>
                    </a>

                    <div class="flex items-center gap-3">
                        <i class="far fa-user text-2xl text-gray-300"></i>
                        <div class="flex flex-col">
                            @if(auth(\'customer\')->check())
                                <span class="text-[10px] font-bold text-gray-300 uppercase leading-tight">Hello, User!</span>
                                <span class="text-xs font-bold leading-tight mt-0.5"><a href="{{ $clean ? $baseUrl.\'/customer/dashboard\' : route(\'shop.customer.dashboard\', $client->slug) }}" class="hover:text-primary transition">Account</a></span>
                            @else
                                <span class="text-[10px] font-bold text-gray-300 uppercase leading-tight">Hello Guest!</span>
                                <span class="text-xs font-bold leading-tight mt-0.5"><a href="{{ $clean ? $baseUrl.\'/login\' : route(\'shop.customer.login\', $client->slug) }}" class="hover:text-primary transition">Sign In</a></span>
                            @endif
                        </div>
                    </div>
                </div>';

if (strpos($content, $findCat) !== false) {
    $content = str_replace($findCat, $replaceCat, $content);
    echo "Category Replaced.\n";
} else {
    echo "Category block not found.\n";
}

if (strpos($content, $findCart) !== false) {
    $content = str_replace($findCart, $replaceCart, $content);
    echo "Cart Replaced.\n";
} else {
    echo "Cart block not found.\n";
}

file_put_contents($file, $content);
