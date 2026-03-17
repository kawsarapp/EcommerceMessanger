<section id="features" class="py-24 bg-[#FDFDFC] dark:bg-[#0a0a0a]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 lg:mb-20">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4 font-bangla tracking-tight">Core <span class="text-brand-500">AI Features</span></h2>
            <p class="text-gray-500 dark:text-gray-400 font-bangla text-lg md:text-xl max-w-2xl mx-auto">সবকিছু একটাই প্ল্যাটফর্মে। আলাদা কোনো টুলের প্রয়োজন নেই।</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
            @if(isset($siteSettings) && (is_array($siteSettings->features) || is_object($siteSettings->features)))
                @foreach($siteSettings->features as $feature)
                @php
                    $color = $feature['color_class'] ?? 'blue';
                    $bgClass = "bg-{$color}-100 dark:bg-{$color}-900/30 text-{$color}-600";
                    if($color === 'cyan') $bgClass = "bg-cyan-100 dark:bg-cyan-900/40 text-cyan-600 dark:text-cyan-400";
                    elseif($color === 'brand') $bgClass = "bg-brand-100 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400";
                    elseif($color === 'green') $bgClass = "bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400";
                    elseif($color === 'purple') $bgClass = "bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400";
                    elseif($color === 'pink') $bgClass = "bg-pink-100 dark:bg-pink-900/40 text-pink-600 dark:text-pink-400";
                    elseif($color === 'orange') $bgClass = "bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400";
                    elseif($color === 'red') $bgClass = "bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400";
                @endphp
                <div class="group p-8 rounded-[2rem] bg-white dark:bg-[#161615] border border-gray-100 dark:border-[#222] hover:border-brand-500/50 hover:shadow-2xl hover:shadow-brand-500/10 transition-all duration-300">
                    <div class="w-14 h-14 {{ $bgClass }} rounded-[1.25rem] flex items-center justify-center text-2xl mb-6 group-hover:scale-110 group-hover:-rotate-3 transition-transform shadow-sm">
                        <i class="{{ $feature['icon'] ?? 'fas fa-star' }}"></i>
                    </div>
                    <h3 class="text-xl md:text-2xl font-bold mb-3 font-bangla text-gray-900 dark:text-gray-100">{{ $feature['title'] ?? '' }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 font-bangla text-sm md:text-base leading-relaxed">
                        {{ $feature['desc'] ?? '' }}
                    </p>
                </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
