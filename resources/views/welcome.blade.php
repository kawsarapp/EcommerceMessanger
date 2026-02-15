<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>NeuralCart · AI sales assistant</title>
    <!-- font & tailwind (inline for portability) -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- additional micro-interactions (no alpine heavy) -->
    <style>
        * { font-family: 'Instrument Sans', system-ui, sans-serif; }
        [x-cloak] { display: none !important; } /* kept for compatibility */
        .gradient-text {
            background: linear-gradient(135deg, #F53003 0%, #FF750F 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hover-lift:hover { transform: translateY(-4px); }
        .transition-all { transition: all 0.2s ease; }
        /* smooth card hover */
        .feature-card {
            transition: transform 0.15s ease, box-shadow 0.2s;
        }
        .feature-card:hover {
            box-shadow: 0 20px 30px -10px rgba(245, 48, 3, 0.15);
        }
        /* blobs animation for right side */
        .pulse-glow {
            animation: pulseGlow 4s infinite alternate;
        }
        @keyframes pulseGlow {
            0% { opacity: 0.4; transform: scale(0.95); }
            100% { opacity: 0.8; transform: scale(1.2); }
        }
        /* right card dummy elements move */
        .mock-order {
            background: linear-gradient(145deg, #fff2f2, #ffe8e8);
        }
        .dark .mock-order {
            background: linear-gradient(145deg, #2a0a0a, #1D0002);
        }
    </style>
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] flex p-5 md:p-8 items-center justify-center min-h-screen flex-col font-['Instrument_Sans'] antialiased">

    <!-- simple header – no login/register, just brand -->
    <header class="w-full max-w-7xl mx-auto mb-8 md:mb-12 px-2">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-gradient-to-br from-[#F53003] to-[#FF750F] rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-md">NC</div>
                <span class="text-2xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-[#1b1b18] to-[#F53003] dark:from-white dark:to-[#FF914D]">Neural<span class="text-[#F53003] dark:text-[#FFA500]">Cart</span></span>
            </div>
            <!-- decorative pill – no registration, just a subtle badge -->
            <div class="text-xs px-4 py-1.5 rounded-full bg-white/70 dark:bg-[#161615]/80 border border-[#e3e3e0] dark:border-[#3E3E3A] shadow-sm font-medium text-[#F53003] hidden sm:block">
                ⚡ AI · beta
            </div>
        </div>
    </header>

    <!-- main content -->
    <main class="w-full max-w-7xl mx-auto px-3 md:px-4">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
            
            <!-- LEFT: content area (fully responsive) -->
            <div class="space-y-7 md:space-y-8 order-2 lg:order-1">

                <!-- tag + headline -->
                <div class="space-y-4">
                    <span class="inline-block px-4 py-1.5 bg-[#fff2f2] dark:bg-[#1D0002] text-[#F53003] rounded-full text-xs font-semibold uppercase tracking-wider border border-red-200 dark:border-red-900/40">
                        🧠 Next‑Gen SaaS · বাংলায় smart
                    </span>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight">
                        Sell Smarter with <br> 
                        <span class="gradient-text text-5xl sm:text-6xl lg:text-7xl">AI Assistants</span>
                    </h1>
                    <p class="text-base sm:text-lg text-[#706f6c] dark:text-[#B0B0A8] max-w-lg leading-relaxed">
                        সয়ংক্রিয় চ্যাটবট, স্মার্ট ইনভেন্টরি এবং পার্সোনালাইজড শপিং পেজ—সবকিছু এখন এক প্ল্যাটফর্মে। 
                        আপনার ব্যবসা পরিচালনা করুন AI এর শক্তি দিয়ে। — <span class="italic">no login, just explore</span>
                    </p>
                </div>

                <!-- feature cards: 2x2 grid (responsive: mobile 1 col, sm 2 col) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-5">
                    <!-- card 1 -->
                    <div class="feature-card p-4 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A] hover:shadow-xl hover:border-[#F53003]/30 transition-all">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center mb-3 text-2xl">💬</div>
                        <h3 class="font-bold text-base mb-1">AI Chatbot</h3>
                        <p class="text-xs text-[#706f6c] dark:text-[#B0B0A8] leading-relaxed">মেসেঞ্জারে অটোমেটিক অর্ডার এবং রিপ্লাই, ২৪/৭ সক্রিয়।</p>
                    </div>
                    <!-- card 2 -->
                    <div class="feature-card p-4 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A] hover:shadow-xl hover:border-[#F53003]/30 transition-all">
                        <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 rounded-xl flex items-center justify-center mb-3 text-2xl">🛒</div>
                        <h3 class="font-bold text-base mb-1">Smart Shop</h3>
                        <p class="text-xs text-[#706f6c] dark:text-[#B0B0A8] leading-relaxed">প্রতিটি ইউজারের জন্য আলাদা স্টোর ফ্রন্ট ও রিকমেন্ডেশন।</p>
                    </div>
                    <!-- card 3 -->
                    <div class="feature-card p-4 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A] hover:shadow-xl hover:border-[#F53003]/30 transition-all">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-xl flex items-center justify-center mb-3 text-2xl">🎤</div>
                        <h3 class="font-bold text-base mb-1">Voice & Vision</h3>
                        <p class="text-xs text-[#706f6c] dark:text-[#B0B0A8] leading-relaxed">AI এখন ছবি এবং ভয়েস মেসেজও বোঝে, অর্ডার করলে চলে।</p>
                    </div>
                    <!-- card 4 -->
                    <div class="feature-card p-4 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A] hover:shadow-xl hover:border-[#F53003]/30 transition-all">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center mb-3 text-2xl">📍</div>
                        <h3 class="font-bold text-base mb-1">Live Tracking</h3>
                        <p class="text-xs text-[#706f6c] dark:text-[#B0B0A8] leading-relaxed">কাস্টমার নিজেই অর্ডারের অবস্থা দেখতে পারবেリアルタイムে।</p>
                    </div>
                </div>

                <!-- CTA group (no register links, just explore & docs) -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 pt-2">
                    <a href="#" class="group px-7 py-3.5 bg-[#F53003] text-white rounded-full font-semibold shadow-xl shadow-orange-500/30 hover:shadow-orange-500/40 hover:scale-[1.03] transition inline-flex items-center gap-2 text-sm sm:text-base">
                        🚀 Explore AI demo
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="#" class="flex items-center gap-2 font-semibold text-[#706f6c] dark:text-gray-300 hover:text-[#F53003] dark:hover:text-[#FF914D] transition text-sm sm:text-base group">
                        <span>📖 API docs</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                </div>

                <!-- tiny note: no login/register required -->
                <div class="flex items-center gap-2 text-xs text-[#A1A09A] pt-2 border-t border-dashed border-[#e3e3e0] dark:border-[#3E3E3A] w-full">
                    <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                    <span>instant preview — zero signup, full responsive</span>
                </div>
            </div>

            <!-- RIGHT: visual (hidden on mobile, visible md:block) but with responsive improvements -->
            <div class="relative order-1 lg:order-2 hidden md:block">
                <!-- background glow animated -->
                <div class="absolute -inset-6 bg-gradient-to-tr from-orange-500/20 to-red-500/20 blur-3xl rounded-full pulse-glow"></div>
                
                <!-- main card mockup -->
                <div class="relative bg-white dark:bg-[#161615] rounded-[2rem] border border-[#e3e3e0] dark:border-[#3E3E3A] shadow-2xl overflow-hidden p-6 md:p-8 backdrop-blur-sm bg-white/70 dark:bg-[#161615]/90">
                    
                    <!-- inner content: live preview style -->
                    <div class="space-y-6">
                        <!-- top bar -->
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            <span class="text-xs text-gray-400 ml-2 font-mono">AI dashboard · live</span>
                        </div>

                        <!-- header skeleton with bengali text -->
                        <div class="h-8 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-[#3E3E3A] dark:to-[#2b2b28] rounded-full w-2/3 flex items-center px-4 text-[10px] text-gray-500 dark:text-gray-300">
                            আজকের অর্ডার: ১৪২টি
                        </div>

                        <!-- message bubbles (ai chatting) -->
                        <div class="space-y-3">
                            <div class="flex gap-2 items-start">
                                <div class="w-6 h-6 rounded-full bg-[#F53003]/20 flex items-center justify-center text-xs">🤖</div>
                                <div class="h-8 bg-gray-100 dark:bg-[#3E3E3A] rounded-2xl rounded-tl-none w-3/5 px-3 py-1.5 text-xs text-gray-600 dark:text-gray-300">আপনার প্রোডাক্ট প্রস্তুত</div>
                            </div>
                            <div class="flex gap-2 items-start justify-end">
                                <div class="h-8 bg-[#F53003]/10 border border-[#F53003]/30 rounded-2xl rounded-tr-none w-2/3 px-3 py-1.5 text-xs text-gray-700 dark:text-orange-100">ছবি পাঠান, আমি দেখছি 🖼️</div>
                                <div class="w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-xs">👤</div>
                            </div>
                        </div>

                        <!-- card grid mock (two boxes) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="aspect-square bg-[#fff2f2] dark:bg-[#1D0002] rounded-xl border border-red-200 dark:border-red-900/50 flex flex-col items-center justify-center p-2 text-center">
                                <span class="text-xs font-bold text-[#F53003]">NEW</span>
                                <span class="text-lg font-bold">৪টি</span>
                                <span class="text-[9px] text-gray-500">অর্ডার পেন্ডিং</span>
                            </div>
                            <div class="aspect-square bg-gray-50 dark:bg-[#0a0a0a] rounded-xl border border-gray-200 dark:border-[#3E3E3A] flex flex-col items-center justify-center p-2">
                                <span class="text-xs text-gray-500">AI 🧠</span>
                                <span class="text-base font-semibold mt-1">ভয়েস অন</span>
                            </div>
                        </div>

                        <!-- bottom bar (like input) -->
                        <div class="h-12 bg-[#1b1b18] dark:bg-white/90 rounded-xl w-full flex items-center px-4 text-white dark:text-black text-xs gap-2">
                            <span class="opacity-70">⚡</span> 
                            <span class="opacity-80">"শাড়ির ছবি দেখাও" — AI typing...</span>
                        </div>

                        <!-- extra small indicator (responsive: shows on mobile as fallback within card) -->
                        <div class="text-[8px] text-center text-gray-400 dark:text-gray-500 flex justify-center items-center gap-1">
                            <span class="inline-block w-1 h-1 bg-green-400 rounded-full"></span> realtime demo · no login wall
                        </div>
                    </div>
                </div>

                <!-- floating badge (decor) -->
                <div class="absolute -bottom-3 -right-3 bg-white dark:bg-[#20201E] rounded-full px-4 py-2 shadow-lg border border-[#e3e3e0] dark:border-[#4a4a46] text-xs font-bold flex gap-1">
                    <span class="text-[#F53003]">🇧🇩</span> বাংলা ready
                </div>
            </div>

            <!-- mobile fallback (small card visible only on small screens) – replicates right side simply -->
            <div class="block md:hidden order-1 mt-4 w-full">
                <div class="relative bg-white dark:bg-[#161615] rounded-2xl border border-[#e3e3e0] dark:border-[#3E3E3A] shadow-xl overflow-hidden p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-[#F53003] text-white text-xs px-3 py-1 rounded-full">⚡ AI LIVE</span>
                        <span class="text-xs text-gray-500">order: ১৪২</span>
                    </div>
                    <div class="flex gap-2 text-sm">
                        <div class="bg-red-50 dark:bg-red-950/40 p-2 rounded-xl w-1/2 text-center">
                            <span class="text-[#F53003] font-bold">৪ pending</span>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-800 p-2 rounded-xl w-1/2 text-center text-xs">
                            🎤 voice enabled
                        </div>
                    </div>
                    <div class="mt-3 h-8 bg-black/10 dark:bg-white/10 rounded-lg flex items-center px-3 text-xs">
                        <span class="opacity-60">🤖 "লাল জামা দেখান"</span>
                    </div>
                    <p class="text-[10px] text-right text-gray-400 mt-2">no login · full responsive</p>
                </div>
            </div>
        </div>
    </main>

    <!-- minimal footer (no login, just ™) -->
    <footer class="mt-16 md:mt-24 py-6 border-t border-[#e3e3e0] dark:border-[#3E3E3A] w-full max-w-7xl mx-auto text-center text-xs text-[#706f6c] dark:text-[#A1A09A] px-3">
        <p class="leading-relaxed">
            © 2026 NeuralCart — AI Commerce Concept. All rights reserved.<br class="sm:hidden"> 
            <span class="inline-block mt-1">Powered by Artificial Intelligence. <span class="text-[#F53003]">সহজ অর্ডার, সহজ বিক্রি।</span></span>
        </p>
    </footer>

    <!-- tiny script for dark mode toggle (just for demo convenience) – but we keep system pref, no extra ui -->
    <script>
        (function() {
            // just to ensure dark mode works with class, but system already respected.
            // add a tiny listener? not needed — but we can add a hidden toggle? no, better keep clean.
            console.log('✨ NeuralCart – pure responsive, no login');
        })();
    </script>
</body>
</html>