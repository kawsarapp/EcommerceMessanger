<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteSetting?->site_name ?? 'Redycart' }} — বাংলাদেশের #১ AI-চালিত eCommerce প্ল্যাটফর্ম</title>
    <meta name="description" content="{{ $siteSetting?->hero_subtitle ?? 'Redycart দিয়ে আপনার অনলাইন শপ খুলুন মিনিটেই। AI chatbot, auto order, courrier booking, flash sale সব এক প্ল্যাটফর্মে।' }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#fff5f0', 100:'#ffe6d8', 200:'#ffcbb0', 300:'#ffa07a', 400:'#ff6b35', 500:'#ff4500', 600:'#e03d00', 700:'#b83300', 800:'#8a2600', 900:'#5c1a00' },
                    },
                    fontFamily: { sans: ['Inter','sans-serif'], bangla: ['Hind Siliguri','sans-serif'] }
                }
            }
        }
    </script>
    <style>
        :root { --primary: #ff4500; --primary-dark: #e03d00; }
        body { font-family: 'Inter', sans-serif; }
        .bangla { font-family: 'Hind Siliguri', sans-serif; }
        .gradient-text { background: linear-gradient(135deg, #ff4500, #ff8c00, #ff4500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; background-size: 200% auto; animation: shimmer 3s linear infinite; }
        @keyframes shimmer { to { background-position: 200% center; } }
        .hero-bg { background: radial-gradient(ellipse at top, #fff5f0 0%, #ffffff 60%); }
        .card-hover { transition: all 0.35s cubic-bezier(.4,0,.2,1); }
        .card-hover:hover { transform: translateY(-6px); box-shadow: 0 25px 50px -12px rgba(255,69,0,0.15); }
        .glow { box-shadow: 0 0 40px rgba(255,69,0,0.25); }
        .blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.4; animation: blob 8s infinite ease-in-out; }
        @keyframes blob { 0%,100%{transform:scale(1) translate(0,0)} 33%{transform:scale(1.1) translate(20px,-20px)} 66%{transform:scale(0.9) translate(-20px,20px)} }
        .plan-popular { background: linear-gradient(135deg, #ff4500, #ff8c00); }
        .plan-card { background: #fff; border: 2px solid #f0f0f0; border-radius: 24px; transition: all 0.3s; }
        .plan-card:hover { border-color: #ff4500; transform: translateY(-4px); box-shadow: 0 20px 40px rgba(255,69,0,0.12); }
        .feature-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 100px; font-size: 13px; font-weight: 600; }
        .chat-bubble-bot { background: linear-gradient(135deg,#ff4500,#ff8c00); color:#fff; border-radius: 18px 18px 18px 4px; }
        .chat-bubble-user { background: #f0f0f0; color: #111; border-radius: 18px 18px 4px 18px; }
        .ticker { display: flex; gap: 40px; animation: ticker 30s linear infinite; white-space: nowrap; }
        @keyframes ticker { from{transform:translateX(0)} to{transform:translateX(-50%)} }
        .stat-card { background: linear-gradient(135deg, rgba(255,69,0,0.06), rgba(255,140,0,0.06)); border: 1px solid rgba(255,69,0,0.15); border-radius: 20px; }
        .nav-link { font-weight: 600; font-size: 14px; color: #555; transition: color .2s; }
        .nav-link:hover { color: #ff4500; }
        .badge-new { background: linear-gradient(90deg,#ff4500,#ff8c00); color: #fff; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 100px; letter-spacing: .05em; }
    </style>
</head>
<body class="antialiased bg-white">

<!-- ══════════════════════════ NAVBAR ══════════════════════════ -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-xl border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-orange-400 flex items-center justify-center shadow-lg">
                <i class="fas fa-bolt text-white text-sm"></i>
            </div>
            <span class="text-xl font-black text-gray-900 tracking-tight">{{ $siteSetting?->site_name ?? 'Redycart' }}</span>
        </a>
        <div class="hidden md:flex items-center gap-8">
            <a href="#features" class="nav-link">Features</a>
            <a href="#themes" class="nav-link">Themes</a>
            <a href="#pricing" class="nav-link">Pricing</a>
            <a href="#faq" class="nav-link">FAQ</a>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('filament.admin.auth.login') }}" class="hidden sm:block text-sm font-semibold text-gray-600 hover:text-primary-500 transition">Login</a>
            <a href="{{ route('filament.admin.auth.register') }}" class="px-5 py-2.5 bg-gradient-to-r from-primary-500 to-orange-400 text-white rounded-xl text-sm font-bold shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5">
                ফ্রি শুরু করুন <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</nav>

<!-- ══════════════════════════ HERO ══════════════════════════ -->
<section class="hero-bg pt-28 pb-16 overflow-hidden relative">
    <div class="blob bg-orange-300 w-96 h-96 -top-20 -right-20"></div>
    <div class="blob bg-red-200 w-80 h-80 bottom-0 -left-20" style="animation-delay:3s"></div>
    
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <div class="text-center max-w-5xl mx-auto">

            <!-- Badge -->
            <div class="inline-flex items-center gap-2 bg-white border border-orange-100 px-4 py-2 rounded-full shadow-sm mb-8">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-primary-500"></span>
                </span>
                <span class="text-xs font-bold text-gray-700 uppercase tracking-widest bangla">{{ $siteSetting?->hero_badge ?? '🇧🇩 বাংলাদেশের সেরা AI eCommerce Platform' }}</span>
            </div>

            <h1 class="text-5xl md:text-7xl xl:text-8xl font-black text-gray-900 leading-[1.05] mb-6 tracking-tight">
                {{ $siteSetting?->hero_title_part1 ?? 'আপনার Online Shop' }}<br>
                <span class="text-4xl md:text-6xl font-black text-gray-700 gradient-text">{{ $siteSetting?->hero_title_part2 ?? 'মিনিটেই তৈরি, AI দিয়ে চালু' }}</span>
            </h1>
            
            <p class="text-lg md:text-xl text-gray-500 mb-10 max-w-3xl mx-auto leading-relaxed bangla">
                {{ $siteSetting?->hero_subtitle ?? 'Redycart দিয়ে আপনার নিজের eCommerce স্টোর খুলুন। AI chatbot অর্ডার নেবে, courier বুক করবে, flash sale চালাবে — আপনি শুধু ঘুমাবেন, AI কাজ করবে।' }}
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
                <a href="{{ route('filament.admin.auth.register') }}" class="group px-8 py-4 bg-gradient-to-r from-primary-500 to-orange-500 text-white rounded-2xl font-bold text-lg shadow-2xl hover:shadow-primary-500/30 transition-all hover:-translate-y-1 flex items-center justify-center gap-2">
                    <i class="fas fa-rocket group-hover:animate-bounce"></i>
                    ৭ দিন ফ্রি — শুরু করুন এখনই
                </a>
                <a href="#features" class="px-8 py-4 bg-white border-2 border-gray-200 text-gray-700 rounded-2xl font-bold text-lg hover:border-primary-300 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-play-circle text-primary-500"></i>
                    Features দেখুন
                </a>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
                <div class="stat-card p-4 text-center">
                    <div class="text-3xl font-black text-primary-500">২৪/৭</div>
                    <div class="text-xs font-semibold text-gray-500 bangla mt-1">AI সক্রিয়</div>
                </div>
                <div class="stat-card p-4 text-center">
                    <div class="text-3xl font-black text-primary-500">৩ মিনিট</div>
                    <div class="text-xs font-semibold text-gray-500 bangla mt-1">শপ তৈরির সময়</div>
                </div>
                <div class="stat-card p-4 text-center">
                    <div class="text-3xl font-black text-primary-500">১৬+</div>
                    <div class="text-xs font-semibold text-gray-500 bangla mt-1">প্রিমিয়াম থিম</div>
                </div>
                <div class="stat-card p-4 text-center">
                    <div class="text-3xl font-black text-primary-500">৪০+</div>
                    <div class="text-xs font-semibold text-gray-500 bangla mt-1">Pro Features</div>
                </div>
            </div>
        </div>

        <!-- Chat Demo -->
        <div class="mt-16 max-w-lg mx-auto bg-white rounded-3xl shadow-2xl p-6 border border-gray-100">
            <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-orange-400 flex items-center justify-center">
                    <i class="fas fa-robot text-white text-sm"></i>
                </div>
                <div>
                    <div class="font-bold text-gray-900 text-sm">Redycart AI Bot</div>
                    <div class="text-xs text-green-500 font-semibold flex items-center gap-1"><span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> অনলাইন</div>
                </div>
                <div class="ml-auto text-xs text-gray-400 bangla">via Facebook Messenger</div>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex gap-2"><div class="w-7 h-7 rounded-full bg-gray-200 shrink-0"></div><div class="chat-bubble-user px-3 py-2 bangla">ভাই কালো পাঞ্জাবি আছে?</div></div>
                <div class="flex gap-2 flex-row-reverse"><div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary-500 to-orange-400 shrink-0 flex items-center justify-center"><i class="fas fa-robot text-white text-xs"></i></div><div class="chat-bubble-bot px-3 py-2 bangla max-w-xs">জ্বি ভাই! কালো কটন পাঞ্জাবি আছে, দাম মাত্র ৮৯০৳। সাইজ কত নেবেন? M, L নাকি XL? 😊</div></div>
                <div class="flex gap-2"><div class="w-7 h-7 rounded-full bg-gray-200 shrink-0"></div><div class="chat-bubble-user px-3 py-2 bangla">L সাইজ। আমার ঠিকানা: ঢাকা, মিরপুর ১২</div></div>
                <div class="flex gap-2 flex-row-reverse"><div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary-500 to-orange-400 shrink-0 flex items-center justify-center"><i class="fas fa-robot text-white text-xs"></i></div><div class="chat-bubble-bot px-3 py-2 bangla max-w-xs">✅ অর্ডার কনফার্ম! ORD-8821 তৈরি হয়ে গেছে। ডেলিভারি ১-২ দিনের মধ্যে। ধন্যবাদ! 🎉</div></div>
            </div>
            <div class="mt-4 bg-green-50 border border-green-200 rounded-xl p-3 flex items-center justify-between">
                <div class="text-xs bangla text-green-700 font-semibold">🚀 AI নিজেই অর্ডার তৈরি করে ফেলল!</div>
                <span class="text-xs text-green-600 font-bold">৮ sec</span>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════ TICKER ══════════════════════════ -->
<div class="bg-gray-900 py-3 overflow-hidden">
    <div class="flex">
        <div class="ticker">
            @foreach(['🤖 AI Auto Reply','⚡ Flash Sale','📦 Auto Courier Booking','🌐 Custom Domain','💬 WhatsApp Bot','📸 Instagram Bot','🎁 Referral Program','⭐ Review System','📊 Analytics','💳 bKash/SSLCommerz','🛒 Abandoned Cart Recovery','🎨 16+ Premium Themes','📧 Email Marketing','🏪 POS Mode','🔍 Advanced SEO','📱 SMS Notification'] as $item)
            <span class="text-white/70 font-medium text-sm whitespace-nowrap">{{ $item }}</span>
            <span class="text-primary-500 font-bold mx-2">·</span>
            @endforeach
            @foreach(['🤖 AI Auto Reply','⚡ Flash Sale','📦 Auto Courier Booking','🌐 Custom Domain','💬 WhatsApp Bot','📸 Instagram Bot','🎁 Referral Program','⭐ Review System','📊 Analytics','💳 bKash/SSLCommerz','🛒 Abandoned Cart Recovery','🎨 16+ Premium Themes','📧 Email Marketing','🏪 POS Mode','🔍 Advanced SEO','📱 SMS Notification'] as $item)
            <span class="text-white/70 font-medium text-sm whitespace-nowrap">{{ $item }}</span>
            <span class="text-primary-500 font-bold mx-2">·</span>
            @endforeach
        </div>
    </div>
</div>

<!-- ══════════════════════════ FEATURES ══════════════════════════ -->
<section id="features" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
            <span class="text-primary-500 font-bold text-sm uppercase tracking-widest">✦ সব কিছু এক জায়গায়</span>
            <h2 class="text-4xl md:text-5xl font-black text-gray-900 mt-3">একটি প্ল্যাটফর্মেই <span class="gradient-text">সব সমাধান</span></h2>
            <p class="text-gray-500 mt-4 max-w-2xl mx-auto bangla text-lg">আলাদা আলাদা সফটওয়্যার কেনার দরকার নেই। Redycart-এ সবই আছে।</p>
        </div>

        <!-- Big Features Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @php
            $dynamicFeatures = $siteSetting?->features ?? [];
            if(empty($dynamicFeatures)) {
                $features = [
                    ['icon'=>'fa-robot','color'=>'from-blue-500 to-indigo-600','bg'=>'bg-blue-50','title'=>'AI Chatbot (Omni-Channel)','bangla'=>'Facebook, WhatsApp, Instagram — সব জায়গায় একই AI কাজ করে। কাস্টমারের প্রশ্নের ১ সেকেন্ডে উত্তর দেয়, প্রোডাক্ট দেখায়, অর্ডার নেয়।','tags'=>['Messenger','WhatsApp','Instagram']],
                    ['icon'=>'fa-store','color'=>'from-primary-500 to-orange-500','bg'=>'bg-orange-50','title'=>'সুন্দর Storefront + ১৬ থিম','bangla'=>'Daraz, Shwapno, Pikabo, Fashion, Luxury সহ ১৬+ প্রিমিয়াম থিম। Custom domain যোগ করুন, brand color দিন — মিনিটেই প্রফেশনাল শপ।','tags'=>['16+ Themes','Custom Domain','Mobile Ready']],
                    ['icon'=>'fa-bolt','color'=>'from-yellow-500 to-orange-500','bg'=>'bg-yellow-50','title'=>'Flash Sale + Countdown','bangla'=>'ঈদ অফার, বিশেষ ছাড় — Flash sale তৈরি করুন countdown timer সহ। AI নিজেই সেল শেষ হলে বন্ধ করে দেয়।','tags'=>['Countdown Timer','Auto End','Banner']],
                    ['icon'=>'fa-truck-fast','color'=>'from-green-500 to-emerald-600','bg'=>'bg-green-50','title'=>'Auto Courier Booking','bangla'=>'Steadfast, Pathao, RedX — ১ ক্লিকেই কুরিয়ারে অর্ডার সাবমিট। Tracking number AI-র কাছে থাকে, কাস্টমারকে জানায়।','tags'=>['Steadfast','Pathao','RedX']],
                    ['icon'=>'fa-bullhorn','color'=>'from-purple-500 to-pink-600','bg'=>'bg-purple-50','title'=>'Marketing Broadcast','bangla'=>'পুরনো হাজারো কাস্টমারকে একসাথে মেসেজ পাঠান। অফার জানান, বিক্রি বাড়ান। Email, SMS, Messenger — সব চ্যানেলে।','tags'=>['Messenger Blast','SMS','Email Campaign']],
                    ['icon'=>'fa-star','color'=>'from-amber-500 to-yellow-500','bg'=>'bg-amber-50','title'=>'Review + Loyalty Points','bangla'=>'Customer review system চালু করুন। Loyalty points দিয়ে কাস্টমার ফেরত আনুন। Referral program দিয়ে নতুন কাস্টমার পান।','tags'=>['Star Rating','Loyalty','Referral']],
                    ['icon'=>'fa-chart-line','color'=>'from-cyan-500 to-blue-600','bg'=>'bg-cyan-50','title'=>'Analytics Dashboard','bangla'=>'Sales report, best products, কোন চ্যানেল থেকে বেশি অর্ডার — সব দেখুন। Export করুন, decision নিন।','tags'=>['Sales Chart','Best Seller','Export']],
                    ['icon'=>'fa-ticket','color'=>'from-rose-500 to-red-600','bg'=>'bg-rose-50','title'=>'Coupon + Partial Payment','bangla'=>'Promo code তৈরি করুন। Advance নেওয়ার সুবিধা দিন। COD, bKash, SSLCommerz — সব payment option।','tags'=>['Promo Code','bKash','COD']],
                    ['icon'=>'fa-shield-halved','color'=>'from-gray-700 to-gray-900','bg'=>'bg-gray-50','title'=>'Staff Accounts + Permissions','bangla'=>'Staff account তৈরি করুন আলাদা permission সহ। শুধু অর্ডার দেখবে নাকি এডিট করবে — আপনি ঠিক করুন।','tags'=>['Multi User','Role Control','Secure']],
                ];
            } else {
                $colorMap = [
                    'blue' => ['color' => 'from-blue-500 to-indigo-600', 'bg' => 'bg-blue-50'],
                    'purple' => ['color' => 'from-purple-500 to-pink-600', 'bg' => 'bg-purple-50'],
                    'green' => ['color' => 'from-green-500 to-emerald-600', 'bg' => 'bg-green-50'],
                    'orange' => ['color' => 'from-orange-500 to-red-500', 'bg' => 'bg-orange-50'],
                    'pink' => ['color' => 'from-pink-500 to-rose-600', 'bg' => 'bg-pink-50'],
                    'cyan' => ['color' => 'from-cyan-500 to-blue-600', 'bg' => 'bg-cyan-50'],
                ];
                $features = [];
                foreach($dynamicFeatures as $df) {
                    $colorInfo = $colorMap[$df['color_class'] ?? 'blue'] ?? $colorMap['blue'];
                    $features[] = [
                        'icon' => $df['icon'] ?? 'fa-star',
                        'title' => $df['title'] ?? '',
                        'bangla' => $df['desc'] ?? '',
                        'color' => $colorInfo['color'],
                        'bg' => $colorInfo['bg'],
                        'tags' => []
                    ];
                }
            }
            @endphp

            @foreach($features as $f)
            <div class="card-hover bg-white border border-gray-100 rounded-2xl p-6 shadow-sm cursor-default">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $f['color'] }} flex items-center justify-center mb-4 shadow-lg">
                    <i class="fas {{ $f['icon'] }} text-white text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $f['title'] }}</h3>
                <p class="text-gray-500 text-sm bangla leading-relaxed mb-4">{{ $f['bangla'] }}</p>
                @if(!empty($f['tags']))
                <div class="flex flex-wrap gap-2">
                    @foreach($f['tags'] as $tag)
                    <span class="feature-pill bg-gray-100 text-gray-600">{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Extra Features List -->
        <div class="bg-gradient-to-br from-gray-50 to-orange-50 rounded-3xl p-8 border border-orange-100">
            <h3 class="text-xl font-bold text-gray-900 mb-6 text-center">আরও যা যা আছে <span class="text-primary-500">(৪০+ Features)</span></h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @php
                $extras = ['📍 Store Locator Map','🔄 Subscription Products','⚖️ Product Comparison','📊 Bulk CSV Import','🎬 Product Video Embed','🔍 Advanced SEO Tools','💱 Multi-Currency','🏪 POS Mode','🔗 Zapier Webhook','📦 Return/Refund Flow','🧾 Custom Checkout','💬 Live Chat Support','🌐 Custom Domain SSL','✨ White-label (No Branding)','🔑 Own API Key','🤖 Multiple AI Models','📸 Instagram DM Bot','📱 SMS Order Alert'];
                @endphp
                @foreach($extras as $extra)
                <div class="flex items-center gap-2 text-sm text-gray-700 font-medium bangla">
                    <span>{{ $extra }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════ THEMES ══════════════════════════ -->
<section id="themes" class="py-20 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <span class="text-primary-400 font-bold text-sm uppercase tracking-widest">🎨 16+ Premium Themes</span>
            <h2 class="text-4xl font-black text-white mt-3">আপনার পছন্দের <span class="gradient-text">ডিজাইন বেছে নিন</span></h2>
            <p class="text-gray-400 mt-3 bangla">এক ক্লিকেই থিম পরিবর্তন করুন — কোনো কোড জানার দরকার নেই</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
            $themes = [
                ['name'=>'Daraz Classic','icon'=>'fa-shopping-bag','color'=>'from-orange-500 to-red-500','desc'=>'Marketplace Style'],
                ['name'=>'Shwapno','icon'=>'fa-cart-shopping','color'=>'from-green-500 to-emerald-600','desc'=>'Grocery & Supermarket'],
                ['name'=>'Fashion Pro','icon'=>'fa-shirt','color'=>'from-pink-500 to-rose-600','desc'=>'Apparel & Clothing'],
                ['name'=>'Luxury Dark','icon'=>'fa-gem','color'=>'from-gray-800 to-gray-900','desc'=>'Jewelry & Watches'],
                ['name'=>'Electronics','icon'=>'fa-microchip','color'=>'from-blue-600 to-indigo-700','desc'=>'Tech & Gadgets'],
                ['name'=>'Pikabo Style','icon'=>'fa-mobile-screen','color'=>'from-purple-500 to-violet-600','desc'=>'Pickaboo Inspired'],
                ['name'=>'Athletic','icon'=>'fa-dumbbell','color'=>'from-yellow-500 to-orange-600','desc'=>'Sports & Fitness'],
                ['name'=>'Vegist Organic','icon'=>'fa-leaf','color'=>'from-green-600 to-teal-600','desc'=>'Organic & Natural'],
            ];
            @endphp
            @foreach($themes as $t)
            <div class="bg-gray-800 rounded-2xl p-5 border border-gray-700 hover:border-primary-500 transition-all card-hover cursor-default">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $t['color'] }} flex items-center justify-center mb-3 shadow-lg">
                    <i class="fas {{ $t['icon'] }} text-white text-sm"></i>
                </div>
                <div class="font-bold text-white text-sm">{{ $t['name'] }}</div>
                <div class="text-gray-500 text-xs mt-1">{{ $t['desc'] }}</div>
            </div>
            @endforeach
        </div>
        <p class="text-center text-gray-500 mt-6 text-sm bangla">+ BDShop, BDPro, Modern, Premium, Default, Shoppers, Kids — আরো থিম আসছে!</p>
    </div>
</section>

<!-- ══════════════════════════ WHY REDYCART ══════════════════════════ -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
            <span class="text-primary-500 font-bold text-sm uppercase tracking-widest">✦ তুলনামূলক বিশ্লেষণ</span>
            <h2 class="text-4xl md:text-5xl font-black text-gray-900 mt-3">কেন <span class="gradient-text">Redycart</span> বেছে নেবেন?</h2>
        </div>
        <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
            <!-- Old Way -->
            <div class="rounded-3xl border-t-4 border-red-400 bg-red-50 p-8 flex flex-col justify-between">
                <div>
                    <div class="text-red-500 font-black text-lg mb-2">❌ আগের পদ্ধতি</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">{{ $siteSetting?->cost_comparison['manual_title'] ?? 'Manual Human Team' }}</h3>
                    
                    @php
                    $painPoints = $siteSetting?->pain_points ?? [];
                    @endphp
                    @if(!empty($painPoints))
                    <div class="mb-8 space-y-3">
                        @foreach($painPoints as $pp)
                        <div class="flex items-start gap-3 bg-red-100/50 p-3 rounded-xl border border-red-100">
                            <div class="w-8 h-8 rounded-full bg-red-100 text-red-500 flex items-center justify-center shrink-0">
                                <i class="{{ $pp['icon'] ?? 'fas fa-exclamation-triangle' }}"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 text-sm bangla">{{ $pp['title'] ?? '' }}</div>
                                <div class="text-xs text-gray-600 bangla mt-1 leading-relaxed">{{ $pp['desc'] ?? '' }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="space-y-3 mb-8">
                    @php
                    $oldCosts = empty($siteSetting?->cost_comparison) ? [
                        ['label'=>'৩ জন স্টাফের বেতন (শিফটিং)','amount'=>'৪৫,০০০৳'],
                        ['label'=>'অফিস ভাড়া ও বিদ্যুৎ','amount'=>'১৫,০০০৳'],
                        ['label'=>'ভুল অর্ডারের লস (গড়)','amount'=>'১০,০০০৳'],
                        ['label'=>'রাত ১২টার পর missed orders','amount'=>'???']
                    ] : [
                        ['label'=> $siteSetting?->cost_comparison['manual_scenario'] ?? '', 'amount'=>''],
                        ['label'=>'স্টাফের বেতন', 'amount'=> $siteSetting?->cost_comparison['manual_salary'] ?? ''],
                        ['label'=>'ওভারহেড খরচ', 'amount'=> $siteSetting?->cost_comparison['manual_overhead'] ?? ''],
                        ['label'=>'ভুল অর্ডারের লস', 'amount'=> $siteSetting?->cost_comparison['manual_loss'] ?? '']
                    ];
                    @endphp
                    @foreach($oldCosts as $c)
                    <div class="flex justify-between border-b border-red-100 pb-3">
                        <span class="text-gray-600 bangla text-sm">{{ $c['label'] }}</span>
                        <span class="font-bold text-red-500">{{ $c['amount'] }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="bg-white rounded-2xl p-4 flex justify-between items-center border-2 border-red-100">
                    <span class="font-bold bangla">মাসিক মোট খরচ</span>
                    <span class="text-2xl font-black text-red-500 text-right">{{ $siteSetting?->cost_comparison['manual_total'] ?? '৭০,০০০৳' }}<span class="block text-sm text-gray-400">/মাস</span></span>
                </div>
            </div>

            <!-- Redycart Way -->
            <div class="rounded-3xl border-t-4 border-primary-500 bg-orange-50 p-8 shadow-xl relative flex flex-col justify-between">
                <div>
                    <div class="absolute -top-4 -right-4 bg-gradient-to-r from-primary-500 to-orange-400 text-white text-xs font-black px-4 py-2 rounded-xl shadow-lg">🏆 Smart Choice</div>
                    <div class="text-primary-500 font-black text-lg mb-2">✅ Redycart পদ্ধতি</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">{{ $siteSetting?->cost_comparison['ai_title'] ?? 'AI-Powered Platform' }}</h3>
                    <ul class="space-y-3 mb-8">
                        @php
                        $aiItems = empty($siteSetting?->cost_comparison) ? 
                            ['২৪/৭ কাজ করে, কোনো ঘুম নেই','১ সেকেন্ডে ইনস্ট্যান্ট রিপ্লাই','১০০% নির্ভুল অর্ডার','আনলিমিটেড কাস্টমার হ্যান্ডেল','Flash sale, analytics, courier সব এক জায়গায়','যেকোনো সময় scale up করুন'] : 
                            [
                                $siteSetting?->cost_comparison['ai_scenario'] ?? '',
                                $siteSetting?->cost_comparison['ai_capacity'] ?? '',
                                $siteSetting?->cost_comparison['ai_accuracy'] ?? '',
                                'বাজেট: ' . ($siteSetting?->cost_comparison['ai_salary'] ?? '০ ৳'),
                            ];
                        // Filter empty items
                        $aiItems = array_filter($aiItems);
                        @endphp
                        @foreach($aiItems as $item)
                        <li class="flex items-center gap-3 text-gray-700 bangla text-sm">
                            <i class="fas fa-check-circle text-green-500 text-base shrink-0"></i>{{ $item }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="bg-white rounded-2xl p-4 flex justify-between items-center border-2 border-primary-100">
                    <span class="font-bold bangla">মাসিক মোট খরচ</span>
                    <div class="text-right">
                        <div class="text-2xl font-black text-primary-500">{{ $siteSetting?->cost_comparison['ai_total'] ?? '১,৯৯৯৳' }}</div>
                        <div class="text-xs text-green-600 font-bold bangla mt-1 flex items-center justify-end gap-1"><i class="fas fa-arrow-down"></i> বিশাল সাশ্রয়!</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════ PRICING ══════════════════════════ -->
<section id="pricing" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <span class="text-primary-500 font-bold text-sm uppercase tracking-widest">✦ সহজ মূল্য পরিকল্পনা</span>
            <h2 class="text-4xl md:text-5xl font-black text-gray-900 mt-3">আপনার বাজেট অনুযায়ী <span class="gradient-text">প্ল্যান বেছে নিন</span></h2>
            <p class="text-gray-500 mt-4 bangla">প্রথম ৭ দিন যেকোনো প্ল্যানে সম্পূর্ণ ফ্রি। কোনো ক্রেডিট কার্ড লাগবে না।</p>
        </div>

        @php
        $plans = [
            ['name'=>'Starter','price'=>'১,৯৯৯','badge'=>'','color'=>'border-gray-200','btnColor'=>'bg-gray-900 hover:bg-gray-800','featured'=>false,'features'=>['১০০টি পণ্য','৫০০ অর্ডার/মাস','AI Bot (Messenger)','৫টি প্রিমিয়াম থিম','Coupon & Review','Custom Domain','Courier Integration','Basic Analytics']],
            ['name'=>'Professional','price'=>'৩,৯৯৯','badge'=>'🔥 Most Popular','color'=>'border-primary-500','btnColor'=>'bg-gradient-to-r from-primary-500 to-orange-400 hover:shadow-lg','featured'=>true,'features'=>['আনলিমিটেড পণ্য','আনলিমিটেড অর্ডার','AI Bot (All Channels)','সব থিম + Premium','Flash Sale + Countdown','WhatsApp & Instagram Bot','Marketing Broadcast','Abandoned Cart Recovery','Loyalty Program + Referral','bKash/SSLCommerz Payment','Advanced Analytics','৩ জন Staff Account']],
            ['name'=>'Enterprise','price'=>'৭,৯৯৯','badge'=>'👑 Best Value','color'=>'border-gray-200','btnColor'=>'bg-gray-900 hover:bg-gray-800','featured'=>false,'features'=>['সব Professional Features','নিজের AI API Key','Email Marketing','SMS Notifications','POS Mode','Bulk Product Import','Subscription Products','Advanced SEO Tools','Zapier/Webhook','১০ জন Staff Account','Priority Support 24/7','White-label (No Branding)']],
        ];
        @endphp

        <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
            @foreach($plans as $plan)
            <div class="plan-card border-2 {{ $plan['color'] }} p-7 relative flex flex-col {{ $plan['featured'] ? 'shadow-2xl md:-translate-y-4' : '' }}">
                @if($plan['badge'])
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-primary-500 to-orange-400 text-white text-xs font-black px-5 py-1.5 rounded-xl shadow-lg">{{ $plan['badge'] }}</div>
                @endif
                <div class="mb-6">
                    <h3 class="text-xl font-black text-gray-900">{{ $plan['name'] }}</h3>
                    <div class="mt-3 flex items-end gap-1">
                        <span class="text-4xl font-black {{ $plan['featured'] ? 'text-primary-500' : 'text-gray-900' }}">{{ $plan['price'] }}৳</span>
                        <span class="text-gray-400 text-sm mb-1">/মাস</span>
                    </div>
                </div>
                <ul class="space-y-2.5 mb-8 flex-1">
                    @foreach($plan['features'] as $f)
                    <li class="flex items-start gap-2 text-sm text-gray-700 bangla">
                        <i class="fas fa-check-circle {{ $plan['featured'] ? 'text-primary-500' : 'text-green-500' }} mt-0.5 shrink-0"></i>{{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('filament.admin.auth.register') }}" class="{{ $plan['btnColor'] }} text-white font-bold py-3.5 rounded-2xl text-center block transition-all hover:-translate-y-0.5">
                    {{ $plan['featured'] ? '🚀 এখনই শুরু করুন' : 'শুরু করুন' }}
                </a>
            </div>
            @endforeach
        </div>
        <p class="text-center text-gray-400 mt-8 text-sm bangla">
            ✅ বার্ষিক প্ল্যানে ২০% ছাড় পাওয়া যায় &nbsp;|&nbsp; ✅ যেকোনো সময় cancel করুন &nbsp;|&nbsp; ✅ বাংলা সাপোর্ট
        </p>
    </div>
</section>

<!-- ══════════════════════════ FAQ ══════════════════════════ -->
<section id="faq" class="py-20 bg-white">
    <div class="max-w-3xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-black text-gray-900">সাধারণ <span class="gradient-text">প্রশ্নোত্তর</span></h2>
        </div>
        @php
        $faqs = [
            ['q'=>'Redycart কি শুধু Facebook-এ কাজ করে?','a'=>'না! Redycart Facebook Messenger, WhatsApp, Instagram DM এবং আপনার নিজের ওয়েবসাইট — সব জায়গায় একসাথে কাজ করে।'],
            ['q'=>'আমার কি কোনো Technical জ্ঞান লাগবে?','a'=>'একদমই না! মাত্র ৩ মিনিটে শপ তৈরি করুন। কোনো coding বা technical জ্ঞানের প্রয়োজন নেই।'],
            ['q'=>'Courier কীভাবে book হবে?','a'=>'Order confirm হলে আপনি ড্যাশবোর্ড থেকে ১ ক্লিকেই Steadfast, Pathao বা RedX-এ পাঠাতে পারবেন।'],
            ['q'=>'Flash Sale কীভাবে কাজ করে?','a'=>'Admin panel থেকে Flash Sale তৈরি করুন, discount ও countdown সেট করুন। AI নিজেই সময় শেষ হলে sale বন্ধ করে দেয়।'],
            ['q'=>'আমার কি Redycart-এর নাম দেখাবে?','a'=>'Enterprise প্ল্যানে White-label option আছে। "Powered by Redycart" সম্পূর্ণ সরিয়ে দিতে পারবেন।'],
        ];
        @endphp
        <div class="space-y-4">
            @foreach($faqs as $faq)
            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-6">
                <h4 class="font-bold text-gray-900 mb-2 bangla">❓ {{ $faq['q'] }}</h4>
                <p class="text-gray-600 text-sm bangla leading-relaxed">{{ $faq['a'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ══════════════════════════ CTA ══════════════════════════ -->
<section class="py-20 bg-gradient-to-br from-gray-900 via-gray-900 to-orange-900 relative overflow-hidden">
    <div class="blob bg-primary-500 w-96 h-96 -top-20 -right-20 opacity-20"></div>
    <div class="blob bg-orange-500 w-72 h-72 bottom-0 left-0 opacity-20" style="animation-delay:4s"></div>
    <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
        <div class="text-6xl mb-6">🚀</div>
        <h2 class="text-4xl md:text-6xl font-black text-white mb-6 bangla">
            আজই শুরু করুন —<br><span class="gradient-text">৭ দিন সম্পূর্ণ ফ্রি!</span>
        </h2>
        <p class="text-gray-300 text-xl mb-10 bangla max-w-2xl mx-auto">
            কোনো ক্রেডিট কার্ড লাগবে না। ৩ মিনিটে শপ তৈরি করুন।<br>আপনার প্রথম AI সেলসম্যান আজ থেকেই কাজ শুরু করুক!
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('filament.admin.auth.register') }}" class="group px-10 py-5 bg-gradient-to-r from-primary-500 to-orange-400 text-white rounded-2xl font-black text-xl shadow-2xl hover:shadow-primary-500/30 transition-all hover:-translate-y-1 flex items-center justify-center gap-3">
                <i class="fas fa-store"></i> ফ্রি শপ তৈরি করুন <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </a>
            <a href="{{ route('filament.admin.auth.login') }}" class="px-10 py-5 bg-white/10 border-2 border-white/20 text-white rounded-2xl font-bold text-xl hover:bg-white/20 transition-all flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i> লগইন করুন
            </a>
        </div>
        <div class="mt-10 flex flex-wrap justify-center gap-6 text-sm text-gray-400">
            <span class="flex items-center gap-2"><i class="fas fa-lock text-green-400"></i> SSL Secured</span>
            <span class="flex items-center gap-2"><i class="fas fa-ban text-red-400"></i> কোনো Hidden Charge নেই</span>
            <span class="flex items-center gap-2"><i class="fas fa-headset text-blue-400"></i> বাংলা Support</span>
            <span class="flex items-center gap-2"><i class="fas fa-rotate-left text-yellow-400"></i> যেকোনো সময় Cancel</span>
        </div>
    </div>
</section>

<!-- ══════════════════════════ FOOTER ══════════════════════════ -->
<footer class="bg-gray-950 text-gray-400 py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <a href="/" class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-500 to-orange-400 flex items-center justify-center">
                        <i class="fas fa-bolt text-white text-xs"></i>
                    </div>
                    <span class="text-white font-black text-lg">{{ $siteSetting?->site_name ?? 'Redycart' }}</span>
                </a>
                <p class="text-sm bangla mb-4">{{ $siteSetting?->footer_text ?? 'বাংলাদেশের #১ AI-চালিত eCommerce প্ল্যাটফর্ম' }}</p>
                <div class="flex flex-col gap-2 text-sm text-gray-500">
                    @if($siteSetting?->phone)<div><i class="fas fa-phone mr-2 w-4 text-center"></i>{{ $siteSetting->phone }}</div>@endif
                    @if($siteSetting?->email)<div><i class="fas fa-envelope mr-2 w-4 text-center"></i>{{ $siteSetting->email }}</div>@endif
                    @if($siteSetting?->address)<div><i class="fas fa-map-marker-alt mr-2 w-4 text-center"></i>{{ $siteSetting->address }}</div>@endif
                </div>
                <div class="flex gap-4 mt-4">
                    @if($siteSetting?->facebook_link)<a href="{{ $siteSetting->facebook_link }}" target="_blank" class="text-gray-500 hover:text-blue-500 transition"><i class="fab fa-facebook fa-lg"></i></a>@endif
                    @if($siteSetting?->youtube_link)<a href="{{ $siteSetting->youtube_link }}" target="_blank" class="text-gray-500 hover:text-red-500 transition"><i class="fab fa-youtube fa-lg"></i></a>@endif
                </div>
            </div>
            <div class="flex flex-col md:flex-row gap-5 md:gap-8 text-sm text-center md:text-left mt-6 md:mt-0">
                <a href="#features" class="hover:text-white transition">Features</a>
                <a href="#pricing" class="hover:text-white transition">Pricing</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="hover:text-primary-400 transition font-semibold">Register</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="hover:text-white transition">Login</a>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-10 pt-8 text-center text-xs bangla">
            &copy; {{ date('Y') }} {{ $siteSetting?->site_name ?? 'Redycart.com' }} — All Rights Reserved. <br class="md:hidden">Powered by {{ $siteSetting?->developer_name ?? 'AI' }} 🤖
        </div>
    </div>
</footer>

</body>
</html>