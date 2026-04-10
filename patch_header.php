<?php
$file = 'resources/views/shop/themes/pikabo/layout.blade.php';
$content = file_get_contents($file);

// 1. Add sticky wrapper to header and nav
$h_old = '<header class="bg-primary text-white py-3 relative z-50 w-full shadow-md">';
$h_new = '<div class="sticky top-0 z-[100] w-full shadow-md">' . "\n    " . '<header class="bg-primary text-white py-3 relative z-50 w-full">';
$content = str_replace($h_old, $h_new, $content);

$nav_old = '<nav class="bg-white text-dark sticky top-[72px] sm:top-[-1px] z-40 hidden md:block border-b border-gray-200 shadow-sm">';
$nav_new = '<nav class="bg-white text-dark z-40 hidden md:block border-b border-gray-200">';
$content = str_replace($nav_old, $nav_new, $content);

$nav_end_old = "</nav>";
$nav_end_new = "</nav>\n</div>";
$content = str_replace($nav_end_old, $nav_end_new, $content);

// 2. Add Login/Register button next to Cartesian
$cart_old = '<a href="{{$clean?$baseUrl.\'/cart\':route(\'shop.cart\',$client->slug)}}"';
$user_login_html = <<<'HTML'
                    {{-- User Account / Login --}}
                    @if(auth('customer')->check())
                        <a href="{{ $clean ? $baseUrl.'/customer/dashboard' : route('shop.customer.dashboard', $client->slug) }}" class="flex items-center text-white hover:text-gray-200 transition font-medium gap-1.5 sm:gap-2">
                            <i class="fas fa-user-circle text-lg"></i>
                            <span class="text-sm hidden sm:block">Account</span>
                        </a>
                    @else
                        <a href="{{ $clean ? $baseUrl.'/customer/login' : route('shop.customer.login', $client->slug) }}" class="flex items-center text-white hover:text-gray-200 transition font-medium gap-1.5 sm:gap-2">
                            <i class="fas fa-user text-lg"></i>
                            <span class="text-sm hidden sm:block">Login</span>
                        </a>
                    @endif

                    
HTML;
$content = str_replace($cart_old, $user_login_html . '                    ' . $cart_old, $content);

file_put_contents($file, $content);
echo "Header Patched!\n";
