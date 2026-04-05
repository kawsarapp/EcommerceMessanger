<?php

// Fix Vegist Head missing auth/login
$file = 'resources/views/shop/themes/vegist/layout.blade.php';
$content = file_get_contents($file);
$find = '                    <button class="hover:text-primary transition text-lg" onclick="document.getElementById(\'mobile-search\').classList.toggle(\'hidden\')"><i class="fas fa-search"></i></button>
                    <a href="{{ $clean ? $baseUrl.\'/track\' : route(\'shop.track\', $client->slug) }}" class="hover:text-primary transition text-lg hidden md:block"><i class="far fa-user"></i></a>';
$replace = '                    <button class="hover:text-primary transition text-lg" onclick="document.getElementById(\'mobile-search\').classList.toggle(\'hidden\')"><i class="fas fa-search"></i></button>
                    @if(auth(\'customer\')->check())
                    <a href="{{ $clean ? $baseUrl.\'/customer/dashboard\' : route(\'shop.customer.dashboard\', $client->slug) }}" class="hover:text-primary transition text-lg hidden md:block"><i class="far fa-user-circle"></i></a>
                    @else
                    <a href="{{ $clean ? $baseUrl.\'/login\' : route(\'shop.customer.login\', $client->slug) }}" class="hover:text-primary transition text-lg hidden md:block"><i class="far fa-user"></i></a>
                    @endif';
if(strpos($content, $find) !== false){
    $content = str_replace($find, $replace, $content);
    file_put_contents($file, $content);
    echo "Vegist auth fixed.\n";
}

// Fix Shwapno
$file2 = 'resources/views/shop/themes/shwapno/layout.blade.php';
$content2 = file_get_contents($file2);
$find2 = '                    <a href="{{ $clean ? $baseUrl.\'/track\' : route(\'shop.track\', $client->slug) }}" class="bg-white/10 hover:bg-white/20 border border-red-400 text-white px-4 py-1.5 text-[11px] font-bold rounded-sm h-9 flex items-center gap-2 transition">
                        <i class="far fa-user text-sm"></i> Track Order
                    </a>';
$replace2 = '                    <a href="{{ $clean ? $baseUrl.\'/track\' : route(\'shop.track\', $client->slug) }}" class="bg-white/10 hover:bg-white/20 border border-red-400 text-white px-4 py-1.5 text-[11px] font-bold rounded-sm h-9 flex items-center gap-2 transition">
                        <i class="fas fa-truck-fast text-sm"></i> Track Order
                    </a>
                    @if(auth(\'customer\')->check())
                    <a href="{{ $clean ? $baseUrl.\'/customer/dashboard\' : route(\'shop.customer.dashboard\', $client->slug) }}" class="bg-white/10 hover:bg-white/20 border border-red-400 text-white px-4 py-1.5 text-[11px] font-bold rounded-sm h-9 flex items-center gap-2 transition">
                        <i class="far fa-user-circle text-sm"></i> Account
                    </a>
                    @else
                    <a href="{{ $clean ? $baseUrl.\'/login\' : route(\'shop.customer.login\', $client->slug) }}" class="bg-white/10 hover:bg-white/20 border border-red-400 text-white px-4 py-1.5 text-[11px] font-bold rounded-sm h-9 flex items-center gap-2 transition">
                        <i class="far fa-user text-sm"></i> Login
                    </a>
                    @endif';
if(strpos($content2, $find2) !== false){
    $content2 = str_replace($find2, $replace2, $content2);
    file_put_contents($file2, $content2);
    echo "Shwapno auth fixed.\n";
}

echo "Batch 1 sweep complete.\n";
