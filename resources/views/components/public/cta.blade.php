<section class="py-12 md:py-16 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto rounded-[2.5rem] bg-brand-500 text-white p-10 md:p-16 text-center relative overflow-hidden shadow-2xl">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 bg-white opacity-5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10">
            <h2 class="text-3xl md:text-5xl lg:text-6xl font-extrabold mb-6 font-bangla tracking-tight leading-tight">ব্যবসা বড় করতে <span class="text-amber-300">প্রস্তুত?</span></h2>
            <p class="text-lg md:text-xl opacity-90 mb-10 max-w-2xl mx-auto font-bangla leading-relaxed">
                আর দেরি করবেন না। আজই শুরু করুন এবং দেখুন AI কিভাবে আপনার সেল দ্বিগুণ করে, সময় বাঁচায় এবং খরচ কমায়।
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/pricing" class="bg-white text-brand-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-gray-100 hover:scale-[1.02] hover:-translate-y-1 transition-all shadow-xl flex items-center justify-center gap-2">
                    Get Started Free <i class="fas fa-arrow-right"></i>
                </a>
                @if(isset($siteSettings) && !empty($siteSettings->phone))
                <a href="tel:{{ $siteSettings->phone }}" class="bg-brand-700 text-white border border-brand-400 px-8 py-4 rounded-full font-bold text-lg hover:bg-brand-800 transition flex items-center justify-center gap-2 shadow-sm">
                    <i class="fas fa-phone-alt"></i> Call Us: {{ $siteSettings->phone }}
                </a>
                @endif
            </div>
            <div class="mt-8 flex items-center justify-center gap-2 text-sm text-brand-100 font-medium">
                <i class="fas fa-shield-alt"></i>
                Set up in 5 minutes. Cancel anytime.
            </div>
        </div>
    </div>
</section>
