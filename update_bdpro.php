<?php
$file = 'resources/views/shop/themes/bdpro/layout.blade.php';
$content = file_get_contents($file);

$find = '            {{-- Category Dropdown Button --}}
            <div class="nav-dropdown h-full relative group">
                <button class="h-full flex items-center gap-3 px-5 bg-white/10 hover:bg-white/20 transition cursor-pointer text-sm font-bold w-60">
                    <i class="fas fa-bars"></i>
                    <span class="flex-1 text-left">Shop by Category</span>
                    <i class="fas fa-chevron-down text-[10px]"></i>
                </button>
                
                {{-- Dropdown Menu --}}
                <div class="dropdown-menu absolute top-full left-0 w-64 bg-white shadow-xl border border-gray-100 rounded-b-lg hidden z-50">
                    <ul class="py-2 text-sm text-gray-700">
                        <li><a href="{{$baseUrl}}?category=all" class="block px-5 py-2 hover:bg-gray-50 hover:text-bdblue font-medium transition"><i class="fas fa-th-large mr-2 w-4 text-center text-gray-400"></i> All Products</a></li>
                        @if(isset($categories))
                            @foreach($categories->take(10) as $c)
                            <li><a href="{{$baseUrl}}?category={{$c->slug}}" class="block px-5 py-2 hover:bg-gray-50 hover:text-bdblue font-medium transition line-clamp-1"><i class="fas fa-caret-right mr-2 w-4 text-center text-gray-400"></i> {{$c->name}}</a></li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>';

$replace = '            {{-- Category Dropdown Button --}}
            <div class="relative group h-full flex items-center bg-white/10 hover:bg-white/20 transition px-5 w-60">
                @include(\'shop.partials.header-category-menu\')
            </div>';

if (strpos($content, $find) !== false) {
    $content = str_replace($find, $replace, $content);
    file_put_contents($file, $content);
    echo "BDPro Layout Updated\n";
} else {
    echo "Could not find target in BDPro Layout\n";
}
