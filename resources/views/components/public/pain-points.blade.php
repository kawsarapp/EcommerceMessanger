<section class="py-20 bg-white dark:bg-[#0a0a0a] border-t border-gray-100 dark:border-gray-900">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-5xl font-extrabold mb-12 font-bangla tracking-tight">
            কেন ম্যানুয়াল সিস্টেমে আপনি <span class="text-red-500">পিছিয়ে পড়ছেন?</span>
        </h2>
        
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-6 lg:gap-8">
            @if(isset($siteSettings) && (is_array($siteSettings->pain_points) || is_object($siteSettings->pain_points)))
                @foreach($siteSettings->pain_points as $point)
                <div class="p-8 rounded-[2rem] bg-gray-50 dark:bg-[#111] hover:bg-red-50 dark:hover:bg-red-900/10 transition-all duration-300 group text-left border border-transparent hover:border-red-100 dark:hover:border-red-900/30">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-900/50 text-red-500 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:scale-110 group-hover:-rotate-3 transition-transform shadow-sm">
                        <i class="{{ $point['icon'] ?? 'fas fa-exclamation-circle' }}"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 font-bangla text-gray-900 dark:text-gray-100">{{ $point['title'] ?? '' }}</h3>
                    <p class="text-gray-500 md:text-base font-bangla leading-relaxed">
                        {{ $point['desc'] ?? '' }}
                    </p>
                </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
