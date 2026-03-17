<section class="relative overflow-hidden pt-10 pb-20 lg:pt-20 lg:pb-32">
    <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl animate-pulse-slow"></div>
    <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-red-500/10 rounded-full blur-3xl"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
            
            <div class="space-y-8 text-center lg:text-left">
                @if(!empty($siteSettings->hero_badge))
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-50 dark:bg-brand-900/30 border border-brand-100 dark:border-brand-900 text-brand-600 dark:text-brand-400 text-sm font-semibold font-bangla shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                    {{ $siteSettings->hero_badge }}
                </div>
                @endif
                
                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold leading-[1.1] tracking-tight">
                    {{ $siteSettings->hero_title_part1 ?? 'আপনার বিজনেসকে করুন' }} <br>
                    <span class="gradient-text">{{ $siteSettings->hero_title_part2 ?? 'Automated Machine' }}</span>
                </h1>
                
                <p class="text-lg sm:text-lg lg:text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto lg:mx-0 font-bangla leading-relaxed">
                    {{ $siteSettings->hero_subtitle ?? '২৪/৭ সাপোর্ট, অটো অর্ডার কনফার্মেশন।' }}
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-4">
                    <a href="/pricing" class="px-8 py-4 bg-brand-500 hover:bg-brand-600 text-white rounded-full font-bold text-lg shadow-xl shadow-brand-500/30 hover:-translate-y-1 transition-all flex items-center justify-center gap-2">
                        🚀 Start Free Trial <i class="fas fa-arrow-right text-sm"></i>
                    </a>
                    <a href="#comparison" class="px-8 py-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-full font-bold text-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all flex items-center justify-center gap-2 font-bangla shadow-sm">
                        💰 খরচের হিসাব দেখুন
                    </a>
                </div>

                <div class="pt-6 flex flex-wrap items-center justify-center lg:justify-start gap-4 text-sm text-gray-500 font-medium">
                    <span class="flex items-center gap-1"><i class="fas fa-check-circle text-green-500"></i> No Credit Card</span>
                    <span class="flex items-center gap-1"><i class="fas fa-check-circle text-green-500"></i> Instant Setup</span>
                    <span class="flex items-center gap-1"><i class="fas fa-check-circle text-green-500"></i> Easy Integration</span>
                </div>
            </div>

            <div class="relative lg:h-[600px] flex items-center justify-center animate-float">
                <div class="relative w-full max-w-md glass-card rounded-3xl p-6 shadow-2xl border-t border-white/50">
                    <div class="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden shadow-inner">
                                <img src="https://ui-avatars.com/api/?name=Customer&background=random" alt="Customer Avatar">
                            </div>
                            <div>
                                <h3 class="font-bold text-sm">Sharmin Akter</h3>
                                <p class="text-xs text-green-500 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full shadow-[0_0_5px_rgba(34,197,94,0.5)]"></span> Online
                                </p>
                            </div>
                        </div>
                        <span class="text-xs font-mono text-gray-400">12:42 PM</span>
                    </div>

                    <div class="space-y-4 font-bangla text-sm">
                        <div class="flex gap-3">
                            <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-2xl rounded-tl-none max-w-[80%] shadow-sm text-gray-700 dark:text-gray-300">
                                ভাইয়া, এই নীল শাড়িটার দাম কত? স্টকে আছে?
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-brand-500 font-medium pl-2">
                            <i class="fas fa-bolt animate-pulse"></i> AI Checking Stock...
                        </div>
                        <div class="flex gap-3 justify-end">
                            <div class="bg-gradient-to-r from-brand-500 to-orange-500 text-white p-3 rounded-2xl rounded-tr-none max-w-[90%] shadow-lg">
                                জি ম্যাম! 😍 এই শাড়িটি আমাদের স্টকে আছে। <br>
                                দাম: ১,২৫০ টাকা। <br>
                                সাথে পাচ্ছেন <span class="font-bold bg-white/20 px-1 rounded inline-block my-1">ফ্রি ডেলিভারি</span>। অর্ডার কনফার্ম করতে "Order Now" বাটনে ক্লিক করুন! 👇
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <div class="bg-white dark:bg-gray-800 p-2 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm w-48">
                                <div class="h-24 bg-gray-100 dark:bg-gray-700 rounded-lg mb-2 relative overflow-hidden flex items-center justify-center">
                                   <i class="fas fa-image text-3xl text-gray-300 dark:text-gray-600"></i>
                                </div>
                                <button class="w-full py-2 bg-black dark:bg-white text-white dark:text-black text-xs font-bold rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition">
                                    Confirm Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="absolute -right-4 md:-right-8 top-20 bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 animate-[bounce_3s_infinite] hidden sm:block">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Today's Sales</p>
                            <p class="text-lg md:text-xl font-bold">৳ ২৫,৪০০</p>
                        </div>
                    </div>
                </div>

                <div class="absolute -left-4 md:-left-12 bottom-32 bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 hidden sm:block">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">AI Replied</p>
                            <p class="text-lg md:text-xl font-bold">1,240 Msgs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
