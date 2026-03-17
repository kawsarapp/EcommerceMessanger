<header class="fixed w-full top-0 z-50 transition-all duration-300 bg-white/80 dark:bg-[#0a0a0a]/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <a href="/" class="flex items-center gap-2 group">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-500 to-orange-500 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg group-hover:rotate-12 transition-transform">
                    {{ substr($siteSettings->site_name ?? 'N', 0, 1) }}
                </div>
                @if(strlen($siteSettings->site_name ?? 'NeuralCart') > 4)
                    <span class="text-2xl font-bold tracking-tight">{{ substr($siteSettings->site_name, 0, -4) }}<span class="text-brand-500">{{ substr($siteSettings->site_name, -4) }}</span></span>
                @else
                    <span class="text-2xl font-bold tracking-tight">{{ $siteSettings->site_name ?? 'NeuralCart' }}</span>
                @endif
            </a>

            <nav class="hidden md:flex gap-8 items-center font-medium text-sm text-gray-600 dark:text-gray-300">
                <a href="/#features" class="hover:text-brand-500 transition">Features</a>
                <a href="/#comparison" class="hover:text-brand-500 transition">Savings Calculator</a>
                <a href="/pricing" class="hover:text-brand-500 transition">Pricing</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="px-5 py-2.5 rounded-full bg-black dark:bg-white text-white dark:text-black hover:bg-brand-500 hover:text-white dark:hover:bg-brand-500 transition-all shadow-md">
                    Login Dashboard
                </a>
            </nav>

            <button id="mobile-menu-btn" class="md:hidden text-2xl text-gray-600" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
    {{-- Mobile Menu --}}
    <div id="mobile-menu" class="hidden md:hidden bg-white dark:bg-[#0a0a0a] border-t border-gray-100 dark:border-gray-800 px-4 pb-4 absolute w-full shadow-lg">
        <div class="flex flex-col gap-4 pt-4 text-center">
            <a href="/#features" class="text-gray-600 dark:text-gray-300 font-semibold hover:text-brand-500 md:text-left block py-2">Features</a>
            <a href="/#comparison" class="text-gray-600 dark:text-gray-300 font-semibold hover:text-brand-500 md:text-left block py-2">Savings Calculator</a>
            <a href="/pricing" class="text-gray-600 dark:text-gray-300 font-semibold hover:text-brand-500 md:text-left block py-2">Pricing</a>
            <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 dark:text-gray-300 font-semibold hover:text-brand-500 md:text-left block py-2">Login</a>
            <a href="{{ route('filament.admin.auth.register') }}" class="bg-brand-500 text-white px-4 py-3 rounded-full font-bold w-full mx-auto max-w-sm mt-2 block shadow-lg">Get Started</a>
        </div>
    </div>
</header>
