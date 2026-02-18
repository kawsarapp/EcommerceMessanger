<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Next-Gen AI Sales & Support | Pricing</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bangla-font { font-family: 'Hind Siliguri', sans-serif; }
        .gradient-text {
            background: linear-gradient(to right, #2563eb, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-gray-50 text-slate-800">

    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-robot text-blue-600"></i> AsianHost AI
            </a>
            
            <nav class="hidden md:flex gap-6 items-center">
                <a href="/" class="text-gray-600 hover:text-gray-900 font-semibold">Home</a>
                <a href="#features" class="text-gray-600 hover:text-gray-900 font-semibold">Features</a>
                <a href="#comparison" class="text-gray-600 hover:text-gray-900 font-semibold">Comparison</a>
                <a href="#plans" class="text-gray-600 hover:text-gray-900 font-semibold">Pricing</a>
                
                {{-- Filament Routes --}}
                <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 hover:text-gray-900 font-semibold">Login</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="bg-blue-600 text-white px-5 py-2.5 rounded-full font-bold hover:bg-blue-700 transition">Get Started</a>
            </nav>

            <div class="md:hidden flex items-center">
                <button id="mobile-menu-btn" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200 p-4 absolute w-full shadow-lg">
            <div class="flex flex-col gap-4">
                <a href="#features" class="text-gray-600 font-semibold">Features</a>
                <a href="#plans" class="text-gray-600 font-semibold">Pricing</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 font-semibold">Login</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="text-blue-600 font-bold">Get Started</a>
            </div>
        </div>
    </header>

    <main>
        <section class="relative py-20 lg:py-32 overflow-hidden bg-white">
            <div class="absolute inset-0 bg-[url('https://play.tailwindcss.com/img/grid.svg')] bg-center [mask-image:linear-gradient(180deg,white,rgba(255,255,255,0))]"></div>
            <div class="relative max-w-7xl mx-auto px-4 text-center">
                <span class="inline-block py-1 px-3 rounded-full bg-blue-100 text-blue-700 font-bold text-sm mb-6">
                    🚀 Next-Gen AI Sales & Support Ecosystem
                </span>
                <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 mb-6 leading-tight bangla-font">
                    আপনার বিজনেসকে করুন <br>
                    <span class="gradient-text">Automated Sales Machine (24/7)</span>
                </h1>
                <p class="text-xl text-gray-500 max-w-3xl mx-auto mb-10 bangla-font leading-relaxed">
                    "Instant Response" এবং "Customer Trust" হলো সফলতার চাবিকাঠি। আমাদের AI সিস্টেম আপনার কাস্টমার সাপোর্ট এবং অর্ডার ম্যানেজমেন্টকে ১০০% অটোমেট করবে এবং মানুষের ভুলের সম্ভাবনা শূন্যের কোঠায় নামিয়ে আনবে।
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#plans" class="bg-blue-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-blue-700 transition shadow-lg hover:shadow-blue-500/30">
                        View Pricing Plans
                    </a>
                    <a href="#comparison" class="bg-white text-gray-700 border border-gray-300 px-8 py-4 rounded-xl font-bold text-lg hover:bg-gray-50 transition">
                        See Cost Comparison
                    </a>
                </div>
            </div>
        </section>

        <section id="comparison" class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 bangla-font">💰 খরচ বনাম সাশ্রয় (Cost Benefit Analysis)</h2>
                    <p class="text-lg text-gray-600 bangla-font">ধরি, আপনার টার্গেট প্রতিদিন ৫০০টি কনফার্ম অর্ডার। দেখুন পার্থক্য:</p>
                </div>

                <div class="grid md:grid-cols-2 gap-8 items-start">
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-red-100">
                        <div class="bg-red-50 p-6 border-b border-red-100">
                            <h3 class="text-2xl font-bold text-red-600 mb-2 flex items-center gap-2">
                                <i class="fas fa-user-times"></i> Manual Human Team
                            </h3>
                            <p class="text-red-800 text-sm bangla-font">Scenario A: ১৫ জন মডারেটর (৩ শিফট)</p>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                                <span class="font-medium text-gray-600">Salary (১৫ জন)</span>
                                <span class="font-bold text-gray-900">১,৫০,০০০ টাকা</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                                <span class="font-medium text-gray-600">Shift & Food</span>
                                <span class="font-bold text-gray-900">৩০,০০০ টাকা</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                                <span class="font-medium text-gray-600">Setup Cost (PC/Bill)</span>
                                <span class="font-bold text-gray-900">৫০,০০০+ টাকা</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4 bg-red-50/50 -mx-6 px-6 py-4">
                                <span class="font-medium text-red-600">Human Error (Loss)</span>
                                <span class="font-bold text-red-600">২০,০০০+ টাকা</span>
                            </div>
                            <div class="pt-2">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-bold text-xl text-gray-800">TOTAL MONTHLY</span>
                                    <span class="font-bold text-xl text-red-600">২,৫০,০০০ টাকা</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-lg text-gray-500">YEARLY COST</span>
                                    <span class="font-bold text-lg text-gray-400 line-through">৩০,০০,০০০ টাকা</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border-2 border-blue-600 relative">
                        <div class="absolute top-0 right-0 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg">WINNER</div>
                        <div class="bg-blue-50 p-6 border-b border-blue-100">
                            <h3 class="text-2xl font-bold text-blue-700 mb-2 flex items-center gap-2">
                                <i class="fas fa-robot"></i> Our AI System
                            </h3>
                            <p class="text-blue-800 text-sm bangla-font">Scenario B: Fully Automated (24/7)</p>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                                <span class="font-medium text-gray-600">Capacity</span>
                                <span class="font-bold text-blue-600">UNLIMITED Chat</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                                <span class="font-medium text-gray-600">Salary / Bonus</span>
                                <span class="font-bold text-green-600">০ টাকা (Zero)</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                                <span class="font-medium text-gray-600">Availability</span>
                                <span class="font-bold text-gray-900">24/7 (No Sleep)</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4 bg-green-50/50 -mx-6 px-6 py-4">
                                <span class="font-medium text-green-700">Accuracy & Speed</span>
                                <span class="font-bold text-green-700">100% / < 1 Sec</span>
                            </div>
                            <div class="pt-2">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-bold text-xl text-gray-800">TOTAL MONTHLY</span>
                                    <span class="font-bold text-xl text-blue-600">~৫,০০০ - ১০,০০০ টাকা*</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-lg text-gray-500">SAVINGS</span>
                                    <span class="font-bold text-lg text-green-500">৯৬% সাশ্রয়!</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Core AI Features</h2>
                    <p class="text-gray-500">সবকিছু হবে অটোমেটিক, নির্ভুল এবং চোখের পলকে।</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="p-8 rounded-2xl bg-gray-50 hover:bg-white hover:shadow-xl transition duration-300 border border-gray-100">
                        <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 text-2xl mb-6">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 bangla-font">Product & Inventory Intelligence</h3>
                        <p class="text-gray-600 bangla-font">
                            স্টক চেক করে অর্ডার নিবে, না থাকলে "Restock Alert" সেট করবে। গ্রাহক বইয়ের ছবি দিলে AI তা পড়ে খুঁজে বের করবে।
                        </p>
                    </div>

                    <div class="p-8 rounded-2xl bg-gray-50 hover:bg-white hover:shadow-xl transition duration-300 border border-gray-100">
                        <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600 text-2xl mb-6">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 bangla-font">Human-Like Behavior</h3>
                        <p class="text-gray-600 bangla-font">
                            গ্রাহক রাগান্বিত থাকলে AI বিনয়ী হবে। "দাম বেশি" বললে প্রোডাক্টের ভ্যালু বুঝিয়ে কনভেন্স করবে। বাংলা ও বাংলিশ বুঝতে সক্ষম।
                        </p>
                    </div>

                    <div class="p-8 rounded-2xl bg-gray-50 hover:bg-white hover:shadow-xl transition duration-300 border border-gray-100">
                        <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center text-green-600 text-2xl mb-6">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 bangla-font">Security & Fraud Detection</h3>
                        <p class="text-gray-600 bangla-font">
                            ফেক অর্ডারকারীদের চিনে রাখবে। স্প্যাম করলে টেলিগ্রামে অ্যালার্ট দিবে। চাইলে আপনি ম্যানুয়ালি টেক-ওভার করতে পারবেন।
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="plans" class="py-20 px-4 bg-gray-50 border-t border-gray-200">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4">Choose Your Perfect Plan</h2>
                <p class="text-xl text-gray-500">Start small or go big. We have a plan for every stage of your business growth.</p>
            </div>

            <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($plans as $plan)
                <div class="relative bg-white rounded-3xl p-8 border transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl flex flex-col
                    {{ $plan->is_featured ? 'border-blue-500 shadow-xl ring-4 ring-blue-500/10' : 'border-gray-200 shadow-sm' }}">
                    
                    @if($plan->is_featured)
                        <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-4 py-1 rounded-full text-sm font-bold shadow-lg">
                            Recommended
                        </div>
                    @endif

                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2" style="color: {{ $plan->color }}">{{ $plan->name }}</h3>
                        <p class="text-gray-500 text-sm h-10">{{ $plan->description }}</p>
                    </div>

                    <div class="mb-8">
                        <span class="text-4xl font-extrabold text-gray-900">৳{{ number_format($plan->price) }}</span>
                        <span class="text-gray-400 font-medium">/ month</span>
                    </div>

                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0"><i class="fas fa-check text-xs"></i></div>
                            <span class="text-gray-700"><strong>{{ $plan->product_limit }}</strong> Products</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0"><i class="fas fa-check text-xs"></i></div>
                            <span class="text-gray-700"><strong>{{ $plan->order_limit }}</strong> Monthly Orders</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0"><i class="fas fa-check text-xs"></i></div>
                            <span class="text-gray-700"><strong>{{ $plan->ai_message_limit }}</strong> AI Replies</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0"><i class="fas fa-check text-xs"></i></div>
                            <span class="text-gray-700">Standard Support</span>
                        </li>
                    </ul>

                    <a href="{{ route('filament.admin.auth.register') }}?plan={{ $plan->id }}" 
                       class="w-full block text-center py-4 rounded-xl font-bold transition transform active:scale-95"
                       style="background-color: {{ $plan->color ?? '#2563eb' }}; color: white; box-shadow: 0 4px 14px 0 {{ $plan->color }}66;">
                        Choose {{ $plan->name }}
                    </a>
                </div>
                @endforeach
                </div>
        </section>

        <section class="py-20 bg-white">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-6 bangla-font">🏆 Final Verdict: সিদ্ধান্ত আপনার</h2>
                <div class="bg-blue-50 rounded-3xl p-8 md:p-12 border border-blue-100">
                    <p class="text-lg text-gray-700 mb-8 bangla-font leading-relaxed">
                        একজন মডারেটরকে মাসে ১০,০০০ টাকা বেতন দিয়েও আপনি ২৪ ঘণ্টা সার্ভিস পাবেন না। ভুল হবে, সেল মিস হবে। 
                        আর আমাদের <strong>AI সিস্টেম মাত্র ১,৪৯৯ টাকা থেকে শুরু</strong> করে আপনাকে দিচ্ছে নির্ভুল, দ্রুত এবং নন-স্টপ সার্ভিস।
                        <br><br>
                        আপনার প্রতিযোগীরা অটোমেশনের দিকে ঝুঁকছে। আপনি কি মান্ধাতার আমলের পদ্ধতি আঁকড়ে ধরে পিছিয়ে থাকবেন, নাকি টেকনোলজি ব্যবহার করে স্মার্ট বিজনেস করবেন?
                    </p>
                    <a href="#plans" class="inline-block bg-gray-900 text-white px-10 py-4 rounded-full font-bold text-lg hover:bg-black transition transform hover:scale-105">
                        Start Your Automation Now <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                    <p class="mt-6 text-gray-500 font-semibold">
                        Call for details: <a href="tel:01771545972" class="text-blue-600 hover:underline">01771545972</a> (Kawsar Ahmed)
                    </p>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-gray-900 text-white py-12 text-center border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4">
            <h3 class="text-2xl font-bold mb-4">AsianHost AI</h3>
            <p class="opacity-50 mb-8">&copy; {{ date('Y') }} AsianHost. All rights reserved.</p>
            <div class="flex justify-center gap-6">
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook fa-lg"></i></a>
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-youtube fa-lg"></i></a>
            </div>
        </div>
    </footer>

    <script>
        // Simple Mobile Menu Toggle
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>

</body>
</html>