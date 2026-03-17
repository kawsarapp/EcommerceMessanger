<footer class="bg-gray-50 dark:bg-[#111] pt-20 pb-10 border-t border-gray-200 dark:border-gray-800 mt-20">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-4 gap-12 mb-16">
            <div class="col-span-1 md:col-span-2">
                <a href="/" class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 bg-brand-500 rounded-lg flex items-center justify-center text-white font-bold">{{ substr($siteSettings->site_name ?? 'N', 0, 1) }}</div>
                    <span class="text-xl font-bold dark:text-white">{{ $siteSettings->site_name ?? 'NeuralCart' }}</span>
                </a>
                <p class="text-gray-500 dark:text-gray-400 mb-6 font-bangla max-w-sm leading-relaxed">
                    {{ $siteSettings->footer_text ?? '' }}
                </p>
                <div class="flex gap-4">
                    @if(!empty($siteSettings->facebook_link))
                        <a href="{{ $siteSettings->facebook_link }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 hover:text-brand-500 hover:border-brand-500 hover:shadow-lg transition-all"><i class="fab fa-facebook-f"></i></a>
                    @endif
                    @if(!empty($siteSettings->youtube_link))
                        <a href="{{ $siteSettings->youtube_link }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 hover:text-brand-500 hover:border-brand-500 hover:shadow-lg transition-all"><i class="fab fa-youtube"></i></a>
                    @endif
                </div>
            </div>
            
            <div>
                <h4 class="font-bold text-gray-900 dark:text-white mb-6">Product</h4>
                <ul class="space-y-4 text-sm text-gray-500 dark:text-gray-400">
                    <li><a href="/#features" class="hover:text-brand-500">Features</a></li>
                    <li><a href="/pricing" class="hover:text-brand-500">Pricing</a></li>
                    <li><a href="{{ route('filament.admin.auth.register') }}" class="hover:text-brand-500">Get Started</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-bold text-gray-900 dark:text-white mb-6">Account</h4>
                <ul class="space-y-4 text-sm text-gray-500 dark:text-gray-400">
                    <li><a href="{{ route('filament.admin.auth.login') }}" class="hover:text-brand-500">Login</a></li>
                    <li><a href="{{ route('filament.admin.auth.register') }}" class="hover:text-brand-500">Register</a></li>
                    @if(!empty($siteSettings->phone))
                    <li><a href="tel:{{ $siteSettings->phone }}" class="hover:text-brand-500">Call Us</a></li>
                    @endif
                    @if(!empty($siteSettings->email))
                    <li><a href="mailto:{{ $siteSettings->email }}" class="hover:text-brand-500">Email Us</a></li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="pt-8 border-t border-gray-200 dark:border-gray-800 text-center text-gray-400 text-sm flex flex-col sm:flex-row justify-between items-center gap-4">
            <p>&copy; {{ date('Y') }} {{ $siteSettings->site_name ?? 'NeuralCart' }}. All rights reserved.</p>
            <p>Developed with ❤️ by <a href="#" class="text-brand-500 font-bold hover:underline">{{ $siteSettings->developer_name ?? 'Kawsar Ahmed' }}</a>.</p>
        </div>
    </div>
</footer>
