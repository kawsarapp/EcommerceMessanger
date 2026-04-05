<?php
$file = 'resources/views/shop/themes/shoppers/layout.blade.php';
$content = file_get_contents($file);

$find = '            {{-- Category Button --}}
            <div class="w-64 h-full relative group">
                <a href="{{$baseUrl}}?category=all" class="h-full flex items-center justify-between px-5 bg-shred hover:bg-red-600 transition text-white text-sm font-bold cursor-pointer">
                    <div class="flex items-center gap-3 flex-1">
                        <span class="tracking-wide">ALL CATEGORIES</span>
                    </div>
                    <i class="fas fa-bars opacity-80"></i>
                </a>
            </div>';

$replace = '            {{-- Category Button --}}
            <div class="relative group h-full flex items-center bg-transparent border-x border-gray-100 hover:bg-gray-50 transition px-5 w-64">
                @include(\'shop.partials.header-category-menu\')
            </div>';

if (strpos($content, $find) !== false) {
    $content = str_replace($find, $replace, $content);
    file_put_contents($file, $content);
    echo "Shoppers Layout Updated\n";
} else {
    echo "Could not find target in Shoppers Layout\n";
}
