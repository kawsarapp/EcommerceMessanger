<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuralCart AI · Pricing Plans</title>
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
        .plan-card:hover { transform: translateY(-8px); }
        .plan-card { transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50 text-slate-800">

    {{-- HEADER --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-violet-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">N</div>
                NeuralCart AI
            </a>
            <nav class="hidden md:flex gap-6 items-center">
                <a href="/" class="text-gray-600 hover:text-gray-900 font-semibold">Home</a>
                <a href="#features" class="text-gray-600 hover:text-gray-900 font-semibold">Features</a>
                <a href="#plans" class="text-gray-600 hover:text-gray-900 font-semibold">Pricing</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 hover:text-gray-900 font-semibold">Login</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="bg-blue-600 text-white px-5 py-2.5 rounded-full font-bold hover:bg-blue-700 transition">Get Started</a>
            </nav>
            <div class="md:hidden">
                <button id="mobile-menu-btn" class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200 p-4 absolute w-full shadow-lg">
            <div class="flex flex-col gap-4">
                <a href="/" class="text-gray-600 font-semibold">Home</a>
                <a href="#plans" class="text-gray-600 font-semibold">Pricing</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 font-semibold">Login</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="text-blue-600 font-bold">Get Started</a>
            </div>
        </div>
    </header>

    <main>

        {{-- HERO --}}
        <section class="relative py-20 lg:py-28 overflow-hidden bg-white">
            <div class="absolute inset-0 bg-[url('https://play.tailwindcss.com/img/grid.svg')] bg-center [mask-image:linear-gradient(180deg,white,rgba(255,255,255,0))]"></div>
            <div class="relative max-w-7xl mx-auto px-4 text-center">
                <span class="inline-block py-1 px-4 rounded-full bg-blue-100 text-blue-700 font-bold text-sm mb-6">
                    💰 Simple, Transparent Pricing
                </span>
                <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 mb-6 leading-tight bangla-font">
                    আপনার বিজনেসের জন্য<br>
                    <span class="gradient-text">সঠিক প্ল্যান বেছে নিন</span>
                </h1>
                <p class="text-xl text-gray-500 max-w-3xl mx-auto mb-10 bangla-font leading-relaxed">
                    ছোট শুরু করুন, বড় হন। কোনো hidden charge নেই। যেকোনো সময় আপগ্রেড করুন।
                </p>
                <div class="flex justify-center gap-6 text-sm text-gray-500 font-medium">
                    <span class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> No Credit Card Required</span>
                    <span class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Cancel Anytime</span>
                    <span class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Instant Setup</span>
                </div>
            </div>
        </section>

        {{-- PLANS SECTION --}}
        <section id="plans" class="py-16 px-4 bg-gray-50 border-t border-gray-200">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($plans->sortBy('sort_order') as $plan)
                <div class="plan-card relative bg-white rounded-3xl border shadow-sm flex flex-col overflow-hidden
                    {{ $plan->is_featured ? 'border-blue-500 shadow-2xl ring-4 ring-blue-500/10' : 'border-gray-200 hover:shadow-xl' }}">

                    {{-- Top Badge --}}
                    @if($plan->badge_text || $plan->is_featured)
                    <div class="text-center py-2.5 text-xs font-bold text-white tracking-wide"
                         style="background: linear-gradient(135deg, {{ $plan->color ?? '#2563eb' }}, {{ $plan->color ?? '#2563eb' }}bb)">
                        @if($plan->badge_text)
                            {{ $plan->badge_text }}
                        @else
                            ⭐ Most Popular
                        @endif
                    </div>
                    @endif

                    <div class="p-8 flex flex-col flex-1">

                        {{-- Plan Name & Desc --}}
                        <div class="mb-5">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl"
                                     style="background-color: {{ $plan->color ?? '#2563eb' }}18">
                                    <span>🚀</span>
                                </div>
                                <h2 class="text-2xl font-extrabold" style="color: {{ $plan->color ?? '#2563eb' }}">
                                    {{ $plan->name }}
                                </h2>
                            </div>
                            @if($plan->description)
                            <p class="text-gray-500 text-sm bangla-font leading-relaxed">{{ $plan->description }}</p>
                            @endif
                        </div>

                        {{-- Pricing --}}
                        <div class="bg-gray-50 rounded-2xl p-4 mb-6">
                            <div class="flex items-end gap-2 mb-1">
                                <span class="text-4xl font-extrabold text-gray-900">৳{{ number_format($plan->price) }}</span>
                                <span class="text-gray-400 font-medium mb-1">/month</span>
                            </div>
                            @if($plan->yearly_price)
                            @php
                                $savings = 0;
                                if ($plan->price > 0) {
                                    $savings = round((($plan->price * 12 - $plan->yearly_price) / ($plan->price * 12)) * 100);
                                }
                            @endphp
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-600">Yearly: <strong>৳{{ number_format($plan->yearly_price) }}</strong></span>
                                @if($savings > 0)
                                <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs font-bold">Save {{ $savings }}%</span>
                                @endif
                            </div>
                            @endif
                            @if($plan->trial_days > 0)
                            <div class="mt-2 flex items-center gap-1.5 text-sm text-blue-600 font-semibold">
                                <i class="fas fa-gift text-xs"></i>
                                {{ $plan->trial_days }}-day Free Trial
                            </div>
                            @endif
                            @if($plan->duration_days && $plan->duration_days != 30)
                            <div class="mt-1 text-xs text-gray-400">Valid for {{ $plan->duration_days }} days</div>
                            @endif
                        </div>

                        {{-- ─── Core Limits ─── --}}
                        <div class="mb-4">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Core Limits</p>
                            <div class="space-y-2.5 text-sm">
                                @php
                                    $limitIcon = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                                @endphp

                                {{-- Products --}}
                                <div class="flex items-center gap-2.5">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-green-600 bg-green-50">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                    <span class="text-gray-700">
                                        <span class="font-bold">{{ $plan->product_limit == 0 ? 'Unlimited' : number_format($plan->product_limit) }}</span> Products
                                    </span>
                                </div>

                                {{-- Orders --}}
                                <div class="flex items-center gap-2.5">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-green-600 bg-green-50">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                    <span class="text-gray-700">
                                        <span class="font-bold">{{ $plan->order_limit == 0 ? 'Unlimited' : number_format($plan->order_limit) }}</span> Monthly Orders
                                    </span>
                                </div>

                                {{-- AI Messages --}}
                                <div class="flex items-center gap-2.5">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-green-600 bg-green-50">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                    <span class="text-gray-700">
                                        <span class="font-bold">{{ $plan->ai_message_limit == 0 ? 'Unlimited' : number_format($plan->ai_message_limit) }}</span> AI Bot Replies/Month
                                    </span>
                                </div>

                                {{-- WhatsApp Limit (if applicable) --}}
                                @if(isset($plan->allow_whatsapp) && $plan->allow_whatsapp)
                                <div class="flex items-center gap-2.5">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-green-600 bg-green-50">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                    <span class="text-gray-700">
                                        <span class="font-bold">{{ isset($plan->whatsapp_limit) && $plan->whatsapp_limit == 0 ? 'Unlimited' : number_format($plan->whatsapp_limit ?? 0) }}</span> WhatsApp Messages/Month
                                    </span>
                                </div>
                                @endif

                                {{-- Storage --}}
                                @if(isset($plan->storage_limit_mb))
                                <div class="flex items-center gap-2.5">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-green-600 bg-green-50">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                    <span class="text-gray-700">
                                        <span class="font-bold">
                                            @if($plan->storage_limit_mb >= 1000)
                                                {{ round($plan->storage_limit_mb/1000, 1) }} GB
                                            @else
                                                {{ $plan->storage_limit_mb }} MB
                                            @endif
                                        </span> File Storage
                                    </span>
                                </div>
                                @endif

                                {{-- Staff Accounts --}}
                                @if(isset($plan->staff_account_limit))
                                <div class="flex items-center gap-2.5">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-green-600 bg-green-50">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                    <span class="text-gray-700">
                                        <span class="font-bold">{{ $plan->staff_account_limit == 0 ? 'Unlimited' : $plan->staff_account_limit }}</span> Staff Account(s)
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- ─── Feature Toggles ─── --}}
                        <div class="mb-4">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Platform & Features</p>
                            <div class="grid grid-cols-1 gap-2 text-sm">
                                @php
                                    $featureList = [
                                        ['key' => 'allow_telegram',     'default' => true,  'label' => 'Telegram Bot',              'icon' => 'fab fa-telegram'],
                                        ['key' => 'allow_whatsapp',     'default' => false, 'label' => 'WhatsApp Bot',              'icon' => 'fab fa-whatsapp'],
                                        ['key' => 'allow_coupon',       'default' => true,  'label' => 'Coupon / Discount System',  'icon' => 'fas fa-tag'],
                                        ['key' => 'allow_review',       'default' => true,  'label' => 'Customer Review System',    'icon' => 'fas fa-star'],
                                        ['key' => 'allow_abandoned_cart','default' => false, 'label' => 'Abandoned Cart Recovery',  'icon' => 'fas fa-cart-arrow-down'],
                                        ['key' => 'allow_marketing_broadcast','default' => false,'label' => 'Marketing Broadcast',  'icon' => 'fas fa-bullhorn'],
                                        ['key' => 'allow_analytics',    'default' => false, 'label' => 'Advanced Analytics',       'icon' => 'fas fa-chart-bar'],
                                        ['key' => 'allow_custom_domain','default' => false, 'label' => 'Custom Domain Connection', 'icon' => 'fas fa-globe'],
                                        ['key' => 'allow_api_access',   'default' => false, 'label' => 'API Access',               'icon' => 'fas fa-code'],
                                        ['key' => 'remove_branding',    'default' => false, 'label' => 'Remove NeuralCart Branding','icon' => 'fas fa-eye-slash'],
                                        ['key' => 'priority_support',   'default' => false, 'label' => 'Priority Support',          'icon' => 'fas fa-headset'],
                                    ];
                                @endphp

                                @foreach($featureList as $feat)
                                @php
                                    $isEnabled = isset($plan->{$feat['key']}) ? (bool)$plan->{$feat['key']} : $feat['default'];
                                @endphp
                                <div class="flex items-center gap-2.5">
                                    @if($isEnabled)
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-green-600 bg-green-50">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                    <span class="text-gray-700 flex items-center gap-1.5">
                                        <i class="{{ $feat['icon'] }} text-xs text-gray-400"></i>
                                        {{ $feat['label'] }}
                                        @if($feat['key'] === 'priority_support' && $isEnabled)
                                        <span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded font-bold">★ VIP</span>
                                        @endif
                                    </span>
                                    @else
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-gray-300 bg-gray-50">
                                        <i class="fas fa-times text-xs"></i>
                                    </div>
                                    <span class="text-gray-400">{{ $feat['label'] }}</span>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ─── Custom Feature Bullets ─── --}}
                        @if(!empty($plan->features) && is_array($plan->features))
                        <div class="mb-4">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Additional Perks</p>
                            <div class="space-y-2 text-sm">
                                @foreach($plan->features as $feature)
                                <div class="flex items-center gap-2.5">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-purple-600 bg-purple-50">
                                        <i class="fas fa-sparkles text-xs"></i>
                                    </div>
                                    <span class="text-gray-700 font-medium">{{ $feature }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Spacer --}}
                        <div class="flex-1"></div>

                        {{-- CTA --}}
                        <div class="mt-6">
                            <a href="{{ route('filament.admin.auth.register') }}?plan={{ $plan->id }}"
                               class="w-full block text-center py-4 rounded-2xl font-bold text-lg transition-all active:scale-95 hover:-translate-y-0.5"
                               style="background: linear-gradient(135deg, {{ $plan->color ?? '#2563eb' }}, {{ $plan->color ?? '#2563eb' }}cc); color: white; box-shadow: 0 8px 20px 0 {{ $plan->color ?? '#2563eb' }}44;">
                                Choose {{ $plan->name }} <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                            <p class="text-center text-xs text-gray-400 mt-3 bangla-font">
                                @if($plan->trial_days > 0)
                                    {{ $plan->trial_days }}-day free trial। কোনো credit card লাগবে না।
                                @else
                                    কোনো hidden charge নেই। যেকোনো সময় বাতিল।
                                @endif
                            </p>
                        </div>

                    </div>
                </div>
                @endforeach
                </div>

                {{-- Call to action below plans --}}
                <div class="text-center mt-16">
                    <p class="text-gray-500 bangla-font text-lg mb-4">কোন প্ল্যান সম্পর্কে সন্দেহ আছে?</p>
                    <a href="tel:01771545972"
                       class="inline-flex items-center gap-3 bg-white border-2 border-gray-200 text-gray-800 px-8 py-4 rounded-full font-bold hover:border-blue-500 hover:text-blue-600 transition">
                        <i class="fas fa-phone-alt text-blue-500"></i>
                        আমাদের কল করুন: 01771545972
                    </a>
                </div>
            </div>
        </section>

        {{-- COST COMPARISON --}}
        <section class="py-20 bg-white border-t border-gray-200">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 bangla-font">💰 খরচ বনাম সাশ্রয়</h2>
                    <p class="text-lg text-gray-600 bangla-font">ধরি, আপনার টার্গেট প্রতিদিন ৫০০টি কনফার্ম অর্ডার:</p>
                </div>

                <div class="grid md:grid-cols-2 gap-8 items-start max-w-4xl mx-auto">
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-red-100">
                        <div class="bg-red-50 p-6 border-b border-red-100">
                            <h3 class="text-2xl font-bold text-red-600 mb-1 flex items-center gap-2">
                                <i class="fas fa-user-times"></i> Manual Human Team
                            </h3>
                            <p class="text-red-800 text-sm bangla-font">Scenario A: ১৫ জন মডারেটর (৩ শিফট)</p>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600">Salary (১৫ জন)</span>
                                <span class="font-bold">১,৫০,০০০ ৳</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600">Shift & Overhead</span>
                                <span class="font-bold">৮০,০০০ ৳</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100 bg-red-50/50 -mx-6 px-6">
                                <span class="text-red-600">Human Error (Loss)</span>
                                <span class="font-bold text-red-600">+২০,০০০ ৳</span>
                            </div>
                            <div class="flex justify-between items-center pt-2">
                                <span class="font-bold text-xl text-gray-800">Monthly Total</span>
                                <span class="font-bold text-xl text-red-600">২,৫০,০০০ ৳</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border-2 border-blue-500 relative">
                        <div class="absolute top-0 right-0 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg">WINNER</div>
                        <div class="bg-blue-50 p-6 border-b border-blue-100">
                            <h3 class="text-2xl font-bold text-blue-700 mb-1 flex items-center gap-2">
                                <i class="fas fa-robot"></i> NeuralCart AI
                            </h3>
                            <p class="text-blue-800 text-sm bangla-font">Scenario B: Fully Automated (24/7)</p>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600">Salary / Bonus</span>
                                <span class="font-bold text-green-600">০ ৳ (Zero)</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600">Capacity</span>
                                <span class="font-bold">UNLIMITED</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100 bg-green-50/50 -mx-6 px-6">
                                <span class="text-green-700">Accuracy</span>
                                <span class="font-bold text-green-700">100% / &lt;1 Sec Reply</span>
                            </div>
                            <div class="flex justify-between items-center pt-2">
                                <span class="font-bold text-xl text-gray-800">Monthly Total</span>
                                <span class="font-bold text-xl text-blue-600">~৫,০০০ - ১০,০০০ ৳</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- FINAL CTA --}}
        <section class="py-20 bg-gray-50 border-t border-gray-200">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-6 bangla-font">🏆 সিদ্ধান্ত আপনার</h2>
                <div class="bg-blue-50 rounded-3xl p-8 md:p-12 border border-blue-100">
                    <p class="text-lg text-gray-700 mb-8 bangla-font leading-relaxed">
                        একজন মডারেটরকে মাসে ১০,০০০ টাকা বেতন দিয়েও আপনি ২৪ ঘণ্টা সার্ভিস পাবেন না। ভুল হবে, সেল মিস হবে।
                        আর আমাদের <strong>AI সিস্টেম</strong> আপনাকে দিচ্ছে নির্ভুল, দ্রুত এবং নন-স্টপ সার্ভিস।
                    </p>
                    <a href="#plans" class="inline-block bg-gray-900 text-white px-10 py-4 rounded-full font-bold text-lg hover:bg-black transition hover:scale-105">
                        Start Your Automation Now <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                    <p class="mt-6 text-gray-500 font-semibold">
                        Call for details: <a href="tel:01771545972" class="text-blue-600 hover:underline">01771545972</a> (Kawsar Ahmed)
                    </p>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-gray-900 text-white py-10 text-center border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-center gap-2 mb-3">
                <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">N</div>
                <span class="text-xl font-bold">NeuralCart AI</span>
            </div>
            <p class="opacity-50 mb-6">&copy; {{ date('Y') }} NeuralCart AI. Developed by Kawsar Ahmed.</p>
            <div class="flex justify-center gap-4">
                <a href="/" class="text-gray-400 hover:text-white text-sm">Home</a>
                <span class="text-gray-700">·</span>
                <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-400 hover:text-white text-sm">Login</a>
                <span class="text-gray-700">·</span>
                <a href="{{ route('filament.admin.auth.register') }}" class="text-gray-400 hover:text-white text-sm">Register</a>
            </div>
        </div>
    </footer>

    <script>
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        if(btn) btn.addEventListener('click', () => menu.classList.toggle('hidden'));
    </script>

</body>
</html>