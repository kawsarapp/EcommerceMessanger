<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'AI Commerce') }} - Smart Sales Assistant</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif

        <style>
            [x-cloak] { display: none !important; }
            .gradient-text {
                background: linear-gradient(135deg, #F53003 0%, #FF750F 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
        </style>
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col font-['Instrument_Sans']">
        
        <header class="w-full lg:max-w-6xl text-sm mb-12">
            <nav class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-[#F53003] rounded-lg flex items-center justify-center text-white font-bold">AI</div>
                    <span class="text-xl font-bold tracking-tight">{{ config('app.name', 'Laravel') }}</span>
                </div>
                
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-5 py-2 border border-[#19140035] dark:border-[#3E3E3A] hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition rounded-full font-medium">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium hover:text-[#F53003] transition">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1c1c1a] rounded-full font-medium hover:opacity-80 transition shadow-lg">Get Started</a>
                        @endif
                    @endauth
                </div>
            </nav>
        </header>

        <main class="w-full lg:max-w-6xl">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                
                {{-- Left Content --}}
                <div class="space-y-8 animate-fade-in">
                    <div class="space-y-4">
                        <span class="px-3 py-1 bg-[#fff2f2] dark:bg-[#1D0002] text-[#F53003] rounded-full text-xs font-bold uppercase tracking-wider">Next-Gen SaaS Platform</span>
                        <h1 class="text-5xl lg:text-7xl font-bold leading-tight">
                            Sell Smarter with <br> <span class="gradient-text">AI Assistants</span>
                        </h1>
                        <p class="text-lg text-[#706f6c] dark:text-[#A1A09A] max-w-lg leading-relaxed">
                            সয়ংক্রিয় চ্যাটবট, স্মার্ট ইনভেন্টরি এবং পার্সোনালাইজড শপিং পেজ—সবকিছু এখন এক প্ল্যাটফর্মে। আপনার ব্যবসা পরিচালনা করুন AI এর শক্তি দিয়ে।
                        </p>
                    </div>

                    {{-- Features List --}}
                    <div class="grid sm:grid-cols-2 gap-6">
                        <div class="p-4 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-lg flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            </div>
                            <h3 class="font-bold mb-1">AI Chatbot</h3>
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">মেসেঞ্জারে অটোমেটিক অর্ডার এবং রিপ্লাই।</p>
                        </div>
                        <div class="p-4 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 text-orange-600 rounded-lg flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <h3 class="font-bold mb-1">Smart Shop</h3>
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">প্রতিটি ইউজারের জন্য আলাদা স্টোর ফ্রন্ট।</p>
                        </div>
                        <div class="p-4 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 text-green-600 rounded-lg flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h3 class="font-bold mb-1">Voice & Vision</h3>
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">AI এখন ছবি এবং ভয়েস মেসেজও বোঝে।</p>
                        </div>
                        <div class="p-4 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 text-purple-600 rounded-lg flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h3 class="font-bold mb-1">Live Tracking</h3>
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">কাস্টমার নিজেই অর্ডারের অবস্থা দেখতে পারবে।</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-6">
                        <a href="{{ route('register') }}" class="px-8 py-4 bg-[#F53003] text-white rounded-full font-bold shadow-xl shadow-orange-500/20 hover:scale-105 transition transform">Start Free Trial</a>
                        <a href="https://laravel.com/docs" target="_blank" class="flex items-center gap-2 font-semibold hover:underline">
                            Explore Documentation
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Right Visual --}}
                <div class="hidden lg:block relative">
                    <div class="absolute -inset-4 bg-gradient-to-tr from-orange-500/10 to-red-500/10 blur-3xl rounded-full"></div>
                    <div class="relative bg-white dark:bg-[#161615] rounded-[2rem] border border-[#e3e3e0] dark:border-[#3E3E3A] shadow-2xl overflow-hidden aspect-[4/5] flex items-center justify-center p-8">
                        {{-- Placeholder for Product Screenshot or Illustration --}}
                        <div class="w-full space-y-6">
                            <div class="h-8 bg-gray-100 dark:bg-[#3E3E3A] rounded-full w-2/3"></div>
                            <div class="space-y-3">
                                <div class="h-4 bg-gray-100 dark:bg-[#3E3E3A] rounded-full w-full"></div>
                                <div class="h-4 bg-gray-100 dark:bg-[#3E3E3A] rounded-full w-full"></div>
                                <div class="h-4 bg-gray-100 dark:bg-[#3E3E3A] rounded-full w-1/2"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="h-32 bg-[#fff2f2] dark:bg-[#1D0002] rounded-2xl border border-red-100 dark:border-red-900/30 flex items-center justify-center">
                                    <span class="text-[#F53003] font-bold">New Order</span>
                                </div>
                                <div class="h-32 bg-gray-50 dark:bg-[#0a0a0a] rounded-2xl border border-gray-100 dark:border-[#3E3E3A] flex items-center justify-center">
                                    <span class="text-gray-400 font-bold">AI Chatting...</span>
                                </div>
                            </div>
                            <div class="h-12 bg-[#1b1b18] dark:bg-white rounded-xl w-full"></div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <footer class="mt-20 py-8 border-t border-[#e3e3e0] dark:border-[#3E3E3A] w-full lg:max-w-6xl text-center text-xs text-[#706f6c] dark:text-[#A1A09A]">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved. <br> Powered by Laravel 12 & Artificial Intelligence.</p>
        </footer>
    </body>
</html>