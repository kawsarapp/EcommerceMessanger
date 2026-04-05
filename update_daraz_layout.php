<?php
$file = 'resources/views/shop/themes/daraz/layout.blade.php';
$content = file_get_contents($file);

$find = '            {{-- Right Actions --}}
            <div class="hidden md:flex items-center gap-6 shrink-0">
                <div class="h-10 relative group flex items-center border-x border-gray-100 px-4">
                    @include(\'shop.partials.header-category-menu\')
                </div>
                <a href="{{$clean?$baseUrl.\'/track\':route(\'shop.track\',$client->slug)}}" class="flex flex-col items-center text-gray-600 hover:text-primary transition group">
                    <i class="fas fa-truck-fast text-xl mb-1 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] font-bold uppercase">?????? ???????</span>
                </a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="flex flex-col items-center text-gray-600 hover:text-blue-600 transition group">
                    <i class="fab fa-facebook-messenger text-xl mb-1 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] font-bold uppercase">?????</span>
                </a>
                @endif
            </div>';

$replace = '            {{-- Right Actions --}}
            <div class="hidden md:flex items-center gap-6 shrink-0">
                <div class="h-10 relative group flex items-center border-x border-gray-100 px-4">
                    @include(\'shop.partials.header-category-menu\')
                </div>
                
                @if(auth(\'customer\')->check())
                <a href="{{$clean?$baseUrl.\'/customer/dashboard\':route(\'shop.customer.dashboard\',$client->slug)}}" class="flex flex-col items-center text-gray-600 hover:text-primary transition group">
                    <i class="far fa-user text-xl mb-1 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] font-bold uppercase">Account</span>
                </a>
                @else
                <a href="{{$clean?$baseUrl.\'/login\':route(\'shop.customer.login\',$client->slug)}}" class="flex flex-col items-center text-gray-600 hover:text-primary transition group">
                    <i class="far fa-user text-xl mb-1 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] font-bold uppercase">Sign In</span>
                </a>
                @endif
                
                @php $bgCartCount = session()->has(\'cart\') ? count(session()->get(\'cart\')) : 0; @endphp
                <a href="{{$clean?$baseUrl.\'/cart\':route(\'shop.cart\',$client->slug)}}" class="relative flex flex-col items-center text-gray-600 hover:text-primary transition group cursor-pointer">
                    <i class="fas fa-shopping-cart text-xl mb-1 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] font-bold uppercase">Cart</span>
                    @if($bgCartCount > 0)
                        <span class="absolute -top-1 -right-2 bg-primary text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full" data-cart-badge>{{ $bgCartCount }}</span>
                    @else
                        <span class="absolute -top-1 -right-2 bg-primary text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full hidden" data-cart-badge>0</span>
                    @endif
                </a>

                <a href="{{$clean?$baseUrl.\'/track\':route(\'shop.track\',$client->slug)}}" class="flex flex-col items-center text-gray-600 hover:text-primary transition group">
                    <i class="fas fa-truck-fast text-xl mb-1 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] font-bold uppercase">Track Order</span>
                </a>
            </div>';

if (strpos($content, $find) !== false) {
    echo "Found block!\n";
    $content = str_replace($find, $replace, $content);
    file_put_contents($file, $content);
} else {
    echo "Daraz Right Actions not found.\n";
}
