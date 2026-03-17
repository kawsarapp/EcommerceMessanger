@php $cost = $siteSettings->cost_comparison ?? []; @endphp
<section id="comparison" class="py-24 bg-gray-900 text-white relative overflow-hidden">
    <div class="absolute inset-0 overflow-hidden opacity-20 pointer-events-none">
        <div class="absolute top-10 left-10 w-72 h-72 bg-blue-500 rounded-full blur-[100px] animate-pulse-slow"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-brand-500 rounded-full blur-[120px] animate-pulse-slow max-md:hidden"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 relative z-10">
        <div class="text-center mb-16 max-w-3xl mx-auto">
            <span class="inline-block px-4 py-1.5 rounded-full bg-brand-900/50 border border-brand-500/30 text-brand-400 font-bold tracking-wider uppercase text-xs mb-4 shadow-sm">Real Data Analysis</span>
            <h2 class="text-3xl md:text-5xl lg:text-5xl font-extrabold mb-6 font-bangla tracking-tight">ম্যানুয়াল টিম vs AI সিস্টেম</h2>
            <p class="text-gray-400 md:text-xl font-bangla leading-relaxed">
                ধরি, আপনার টার্গেট প্রতিদিন ৫০০টি অর্ডার। এই অপারেশন চালাতে আপনার খরচের পার্থক্য দেখুন।
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 lg:gap-12 px-2 lg:px-8">
            {{-- Manual --}}
            <div class="bg-white/5 backdrop-blur-md rounded-[2.5rem] p-8 md:p-10 border border-white/10 shadow-xl transition-all hover:bg-white/10">
                <h3 class="text-xl md:text-3xl font-bold text-red-400 mb-2 flex items-center gap-3">
                    <i class="fas fa-users-slash opacity-80"></i> {{ $cost['manual_title'] ?? 'Manual Human Team' }}
                </h3>
                <p class="text-gray-400 text-sm md:text-base mb-8 border-b border-gray-700/50 pb-5 bangla-font">{{ $cost['manual_scenario'] ?? 'Scenario A: ১৫ জন মডারেটর (৩ শিফট)' }}</p>

                <div class="space-y-6 font-bangla">
                    <div class="flex justify-between items-center bg-gray-900/40 p-3 rounded-xl">
                        <span class="text-gray-300 font-medium">স্টাফ স্যালারি</span>
                        <span class="font-bold text-lg md:text-xl">{{ $cost['manual_salary'] ?? '১,৫০,০০০ ৳' }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-gray-900/40 p-3 rounded-xl">
                        <span class="text-gray-300 font-medium">শিফট ও অন্যান্য খরচ</span>
                        <span class="font-bold text-lg md:text-xl">{{ $cost['manual_overhead'] ?? '৮০,০০০ ৳' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-red-400 bg-red-900/20 p-3 rounded-xl border border-red-900/30">
                        <span class="font-medium">হিউম্যান এরর (Loss)</span>
                        <span class="font-bold text-lg md:text-xl">+{{ $cost['manual_loss'] ?? '২০,০০০ ৳' }}</span>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-700/50">
                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Total Monthly Cost</p>
                            <p class="text-4xl md:text-5xl font-extrabold text-white tracking-tighter">{{ $cost['manual_total'] ?? '২,৫০,০০০ ৳' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- AI --}}
            <div class="bg-gradient-to-b from-brand-900 to-black rounded-[2.5rem] p-8 md:p-10 border-2 border-brand-500/50 shadow-[0_0_40px_rgba(245,48,3,0.3)] relative overflow-hidden md:-translate-y-4 hover:shadow-[0_0_60px_rgba(245,48,3,0.4)] transition-all">
                <div class="absolute top-0 right-0 bg-brand-500 text-white text-xs font-black px-6 py-2 rounded-bl-3xl shadow-lg uppercase tracking-widest z-10">Best Choice</div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-[0.03]"></div>
                
                <h3 class="text-xl md:text-3xl font-bold text-brand-400 mb-2 flex items-center gap-3 relative z-10">
                    <i class="fas fa-robot"></i> {{ $cost['ai_title'] ?? 'NeuralCart AI' }}
                </h3>
                <p class="text-gray-400 text-sm md:text-base mb-8 border-b border-gray-800 pb-5 bangla-font relative z-10">{{ $cost['ai_scenario'] ?? 'Scenario B: Fully Automated (24/7)' }}</p>

                <div class="space-y-6 font-bangla relative z-10">
                    <div class="flex justify-between items-center bg-black/50 p-3 rounded-xl">
                        <span class="text-gray-300 font-medium">স্টাফ স্যালারি</span>
                        <span class="font-bold text-lg md:text-xl text-green-400">{{ $cost['ai_salary'] ?? '০ ৳ (Zero)' }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-black/50 p-3 rounded-xl">
                        <span class="text-gray-300 font-medium">ক্যাপাসিটি</span>
                        <span class="font-bold text-lg md:text-xl">{{ $cost['ai_capacity'] ?? 'UNLIMITED' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-brand-400 bg-brand-900/30 p-3 rounded-xl border border-brand-800">
                        <span class="font-medium">Accuracy</span>
                        <span class="font-bold text-lg md:text-xl">{{ $cost['ai_accuracy'] ?? '100% / <1 Sec Reply' }}</span>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-800 relative z-10">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                        <div>
                            <p class="text-xs text-brand-300 font-bold uppercase tracking-wider mb-1">Total Monthly Cost</p>
                            <p class="text-4xl md:text-5xl font-extrabold text-white tracking-tighter">{{ $cost['ai_total'] ?? '৫,০০০ ৳' }}</p>
                        </div>
                        <div class="text-left sm:text-right bg-green-900/40 text-green-400 rounded-xl px-4 py-2 border border-green-800">
                            <p class="text-xs uppercase font-bold tracking-wide">Savings</p>
                            <p class="text-2xl font-black">Huge!</p>
                        </div>
                    </div>
                    <a href="/pricing" class="mt-8 block w-full py-4 bg-brand-600 hover:bg-brand-500 text-white text-center rounded-2xl font-bold text-lg transition-transform active:scale-95 shadow-[0_4px_15px_rgba(0,0,0,0.5)]">
                        Get Started Now
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>
