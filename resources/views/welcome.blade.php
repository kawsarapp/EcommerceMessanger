@extends('layouts.public')

@section('custom_styles')
<style>
    /* Premium Animations & Effects */
    .bg-grid-pattern {
        background-size: 40px 40px;
        background-image: linear-gradient(to right, rgba(0, 0, 0, 0.05) 1px, transparent 1px),
                          linear-gradient(to bottom, rgba(0, 0, 0, 0.05) 1px, transparent 1px);
        mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
        -webkit-mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
    }
    .dark .bg-grid-pattern {
        background-image: linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                          linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
    }
    
    .blob {
        position: absolute;
        filter: blur(80px);
        z-index: 0;
        opacity: 0.6;
        animation: rotateBlob 20s infinite linear;
    }
    @keyframes rotateBlob {
        0% { transform: rotate(0deg) scale(1); }
        50% { transform: rotate(180deg) scale(1.2); }
        100% { transform: rotate(360deg) scale(1); }
    }

    .bento-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 24px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        position: relative;
    }
    .dark .bento-card {
        background: rgba(20, 20, 20, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .bento-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
        border-color: rgba(245, 48, 3, 0.3);
    }
    .dark .bento-card:hover {
        box-shadow: 0 20px 40px -10px rgba(245, 48, 3, 0.15);
    }
    
    .text-gradient {
        background: linear-gradient(135deg, #F53003 0%, #FF750F 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .text-gradient-purple {
        background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .text-gradient-blue {
        background: linear-gradient(135deg, #3B82F6 0%, #06B6D4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .feature-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 16px;
    }
    
    .code-block {
        background: #0f172a;
        border-radius: 12px;
        padding: 16px;
        font-family: monospace;
        color: #38bdf8;
        font-size: 13px;
        position: relative;
        overflow: hidden;
    }
    .code-block::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 30px;
        background: rgba(255,255,255,0.05);
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .code-dot {
        width: 10px; height: 10px; border-radius: 50%;
        display: inline-block; margin-right: 6px;
        margin-top: 10px;
    }
</style>
@endsection

@section('content')
<div class="relative overflow-hidden selection:bg-brand-500 selection:text-white">
    
    <!-- Background Elements -->
    <div class="absolute inset-0 bg-grid-pattern z-0"></div>
    <div class="blob bg-brand-400/20 dark:bg-brand-500/20 w-[600px] h-[600px] rounded-full top-[-20%] left-[-10%]"></div>
    <div class="blob bg-purple-400/20 dark:bg-purple-600/20 w-[500px] h-[500px] rounded-full bottom-[10%] right-[-5%] animation-delay-2000"></div>
    <div class="blob bg-blue-400/20 dark:bg-blue-600/20 w-[400px] h-[400px] rounded-full top-[40%] left-[40%] animation-delay-4000"></div>

    <div class="container mx-auto px-4 relative z-10 pt-16 pb-24">
        
        <!-- HERO SECTION -->
        <div class="text-center max-w-4xl mx-auto mb-20">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm mb-8 animate-float">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-brand-500"></span>
                </span>
                <span class="text-sm font-semibold tracking-wide text-gray-800 dark:text-gray-200 uppercase">বাংলাদেশের #১ সম্পূর্ণ অটোমেটেড ই-কমার্স এআই</span>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-6 leading-tight font-bangla">
                আপনার ব্যবসাকে করুন <br>
                <span class="text-gradient">Automated Machine</span>
            </h1>
            
            <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 mb-10 leading-relaxed font-bangla max-w-3xl mx-auto">
                ২৪/৭ কাস্টমার সাপোর্ট, অটোমেটিক অর্ডার ক্রিয়েশন, লাইভ ইনভেন্টরি সিঙ্ক এবং কুরিয়ার বুকিং। 
                আপনি ঘুমালেও আপনার এআই সেলসম্যান কাস্টমার হ্যান্ডেল করবে নিখুঁতভাবে।
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('filament.admin.auth.register') }}" class="px-8 py-4 bg-brand-500 hover:bg-brand-600 text-white rounded-xl font-bold text-lg shadow-[0_10px_30px_-10px_rgba(245,48,3,0.5)] transition-all transform hover:-translate-y-1 w-full sm:w-auto flex items-center justify-center gap-2">
                    <i class="fas fa-rocket"></i> ৭ দিনের ফ্রি ট্রায়াল শুরু করুন
                </a>
                <a href="#features" class="px-8 py-4 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl font-bold text-lg transition-all w-full sm:w-auto flex items-center justify-center gap-2">
                    <i class="far fa-play-circle text-brand-500"></i> ডেমো ভিডিও দেখুন
                </a>
            </div>
            
            <!-- Integrations Banner -->
            <div class="mt-16 pt-10 border-t border-gray-200 dark:border-gray-800">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-6">যেসব প্ল্যাটফর্মে সরাসরি সাপোর্ট করে</p>
                <div class="flex flex-wrap justify-center items-center gap-6 md:gap-12 opacity-70 grayscale hover:grayscale-0 transition-all duration-500">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/archive/0/05/20191206121908%21Facebook_Messenger_logo_2013.svg" class="h-10 hover:scale-110 transition-transform" alt="Messenger">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/WhatsApp_icon.png" class="h-10 hover:scale-110 transition-transform" alt="WhatsApp">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/9/9d/WooCommerce_logo.svg" class="h-8 hover:scale-110 transition-transform" alt="WooCommerce">
                    <div class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fab fa-node-js text-green-500"></i> Node.js
                    </div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fab fa-php text-indigo-500"></i> PHP
                    </div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fab fa-html5 text-orange-500"></i> Custom HTML
                    </div>
                </div>
            </div>
        </div>

        <!-- THE BENTO BOX FEATURES GRID -->
        <div id="features" class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-24">
            
            <!-- Smart Chatbot (Span 8) -->
            <div class="bento-card md:col-span-8 p-8 flex flex-col md:flex-row gap-8 items-center">
                <div class="flex-1">
                    <div class="feature-icon-wrapper bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Omni-Channel AI Chatbot</h3>
                    <p class="text-gray-600 dark:text-gray-400 font-bangla mb-4 leading-relaxed">
                        ফেসবুক মেসেঞ্জার, হোয়াটসঅ্যাপ এবং আপনার ওয়েবসাইটের লাইভ চ্যাটে একই সাথে কাজ করে। 
                        মানুষের মতো সাবলীলভাবে কথা বলে, কাস্টমারকে কনভিন্স করে এবং সরাসরি চ্যাট থেকেই অর্ডার কালেক্ট করে।
                    </p>
                    <ul class="space-y-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> ন্যাচারাল ল্যাঙ্গুয়েজ প্রসেসিং (NLP)</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> প্রোডাক্টের ছবি ও ভিডিও শো করে</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> কাস্টমারের নাম ধরে সম্বোধন করে</li>
                    </ul>
                </div>
                <div class="w-full md:w-[280px] bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 shadow-inner relative">
                    <!-- Chat UI Mockup -->
                    <div class="space-y-3 test-xs">
                        <div class="flex gap-2">
                            <div class="w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 flex-shrink-0"></div>
                            <div class="bg-gray-200 dark:bg-gray-700 p-2 rounded-r-lg rounded-bl-lg text-xs dark:text-gray-300">ভাইয়া, কালো কালারের পাঞ্জাবি আছে?</div>
                        </div>
                        <div class="flex gap-2 flex-row-reverse">
                            <div class="w-6 h-6 rounded-full bg-brand-500 flex-shrink-0 flex items-center justify-center text-[10px] text-white"><i class="fas fa-robot"></i></div>
                            <div class="bg-brand-500 text-white p-2 rounded-l-lg rounded-br-lg text-xs">জ্বি ভাইয়া! কালো কালারের ২ টি ডিজাইন এভেইলেবল আছে। দাম মাত্র ৮৯০ টাকা। স্টক ফুরিয়ে যাওয়ার আগেই অর্ডার কনফার্ম করবেন কি?</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Auto Order Creation (Span 4) -->
            <div class="bento-card md:col-span-4 p-8 bg-gradient-to-br from-white to-orange-50 dark:from-gray-900 dark:to-orange-900/10">
                <div class="feature-icon-wrapper bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                    <i class="fas fa-cart-arrow-down"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Auto Order & Invoice</h3>
                <p class="text-gray-600 dark:text-gray-400 font-bangla mb-4">
                    কাস্টমার চ্যাটে অ্যাড্রেস আর নাম্বার দিলেই এআই নিজে ডাটাবেসে অর্ডার ক্রিয়েট করে ফেলবে। আপনাকে ম্যানুয়ালি কিছুই টাইপ করতে হবে না।
                </p>
                <div class="mt-auto bg-white dark:bg-gray-800 rounded-lg p-3 border border-orange-100 dark:border-orange-900/30 flex items-center justify-between">
                    <div>
                        <div class="text-xs text-gray-500">New Order Created</div>
                        <div class="font-bold text-gray-900 dark:text-white">ORD-5924 <span class="text-brand-500">৳ ৮৯০</span></div>
                    </div>
                    <span class="text-green-500"><i class="fas fa-check-circle text-xl"></i></span>
                </div>
            </div>

            <!-- Developers API / Easy Connect (Span 6) -->
            <div class="bento-card md:col-span-6 p-8">
                <div class="feature-icon-wrapper bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                    <i class="fas fa-code"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Easy Website Integration</h3>
                <p class="text-gray-600 dark:text-gray-400 font-bangla mb-4">
                    ওয়ার্ডপ্রেসের জন্য রেডিমেড প্লাগিন আছে। আর কাস্টম সাইট? মাত্র ২ লাইন জেসন কোড বসিয়ে আপনার গোটা ডাটাবেস এআই-এর ব্রেইনে কানেক্ট করে ফেলুন! 
                </p>
                
                <div class="code-block mt-4">
                    <div class="absolute top-2 left-3 flex gap-1.5">
                        <span class="bg-red-400 code-dot mt-0"></span><span class="bg-yellow-400 code-dot mt-0"></span><span class="bg-green-400 code-dot mt-0"></span>
                    </div>
<div class="mt-5 text-gray-300">
<span class="text-purple-400">&lt;!-- Paste before &lt;/body&gt; --&gt;</span><br>
<span class="text-blue-400">&lt;script&gt;</span><br>
&nbsp;&nbsp;window.AICB_KEY = <span class="text-green-400">'sk-live-xw9...'</span>;<br>
<span class="text-blue-400">&lt;/script&gt;</span><br>
<span class="text-blue-400">&lt;script</span> <span class="text-sky-300">src</span>=<span class="text-green-400">"https://api.neuralcart.com/chat.js"</span> <span class="text-sky-300">async</span><span class="text-blue-400">&gt;&lt;/script&gt;</span>
</div>
                </div>
            </div>

            <!-- Real-time Sync & Central Inbox (Span 6) -->
            <div class="bento-card md:col-span-6 p-8 relative overflow-hidden">
                <div class="absolute right-0 top-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>
                <div class="feature-icon-wrapper bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Live Sync & Central Inbox</h3>
                <p class="text-gray-600 dark:text-gray-400 font-bangla mb-4">
                    স্টক শেষ হলে এআই সাথে সাথে অর্ডার নেয়া বন্ধ করে দিবে। সবগুলো প্ল্যাটফর্মের চ্যাট এবং অর্ডার আপনি একটিমাত্র অ্যাডমিন প্যানেল (Live Inbox) থেকে কন্ট্রোল করতে পারবেন।
                </p>
                <div class="flex gap-4 mt-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl p-4 flex-1 text-center font-bold">
                        <i class="fab fa-facebook-messenger text-3xl text-blue-500 mb-2"></i>
                        <div class="text-sm dark:text-white">Messenger</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl p-4 flex-1 text-center font-bold">
                        <i class="fab fa-whatsapp text-3xl text-green-500 mb-2"></i>
                        <div class="text-sm dark:text-white">WhatsApp</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl p-4 flex-1 text-center font-bold">
                        <i class="fas fa-globe text-3xl text-purple-500 mb-2"></i>
                        <div class="text-sm dark:text-white">Website Chat</div>
                    </div>
                </div>
            </div>

            <!-- Auto Courier / Marketing / Staff (Grid within Grid) -->
            <div class="col-span-1 md:col-span-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Courier -->
                <div class="bento-card p-6">
                    <div class="text-3xl text-pink-500 mb-3"><i class="fas fa-truck-fast"></i></div>
                    <h4 class="font-bold text-gray-900 dark:text-white text-lg mb-2">Auto Courier Booking</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-bangla">Steadfast বা Pathao কুরিয়ারে ম্যানুয়ালি এন্ট্রি করার দিন শেষ। ড্যাশবোর্ড থেকে ১ ক্লিকেই অর্ডার কুরিয়ারে সাবমিট হয়ে যাবে। ডেলিভারি স্ট্যাটাস এআই ট্র‍্যাক করবে।</p>
                </div>
                
                <!-- Marketing Broadcast -->
                <div class="bento-card p-6">
                    <div class="text-3xl text-green-500 mb-3"><i class="fas fa-bullhorn"></i></div>
                    <h4 class="font-bold text-gray-900 dark:text-white text-lg mb-2">Marketing Broadcast</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-bangla">ঈদের অফার বা ডিসকাউন্টে পুরনো হাজারো কাস্টমারকে এক ক্লিকেই মেসেজ পাঠান। বিল্ট-ইন মার্কেটিং টুলস সেলস দ্বিগুণ করবে।</p>
                </div>

                <!-- Custom Domain & SaaS -->
                <div class="bento-card p-6">
                    <div class="text-3xl text-blue-500 mb-3"><i class="fas fa-building-shield"></i></div>
                    <h4 class="font-bold text-gray-900 dark:text-white text-lg mb-2">SaaS & Custom Domain</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-bangla">মাল্টি-ট্যানেন্ট সাপোর্ট। নিজের ব্র্যান্ডের কাস্টম ডোমেইন অ্যাড করে পোর্টাল ইউজ করুন। স্টাফদের আলাদা আলাদা পারমিশন সেট করে দিন।</p>
                </div>
            </div>
        </div>


        <!-- COST COMPARISON -->
        <div class="mb-24">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">কেন AI এর পেছনে ইনভেস্ট করবেন?</h2>
                <p class="text-gray-500 mt-2 font-bangla">Traditional টিমের তুলনায় এআই ইউজ করা কতটা সাশ্রয়ী, তার একটি চিত্র।</p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Manual -->
                <div class="bg-white dark:bg-gray-800 border-t-4 border-red-500 shadow-lg rounded-2xl p-8 relative opacity-90">
                    <div class="absolute -top-4 right-6 bg-red-100 text-red-600 text-xs font-bold px-3 py-1 rounded-full"><i class="fas fa-times"></i> Old System</div>
                    <h3 class="text-xl font-bold dark:text-white mb-6">Traditional Human Team</h3>
                    
                    <div class="space-y-4 mb-8">
                        <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                            <span class="text-gray-600 dark:text-gray-400">৩ জন স্টাফের মাসিক বেতন (শিফটিং)</span>
                            <span class="font-semibold text-gray-800 dark:text-white">৪৫,০০০ ৳</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                            <span class="text-gray-600 dark:text-gray-400">অফিস স্পেস ও ইলেকট্রিসিটি</span>
                            <span class="font-semibold text-gray-800 dark:text-white">১৫,০০০ ৳</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                            <span class="text-gray-600 dark:text-gray-400">ভুল অর্ডারের কারণে লস (গড়)</span>
                            <span class="font-semibold text-gray-800 dark:text-white">১০,০০০ ৳</span>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-xl flex justify-between items-center">
                        <span class="font-bold text-gray-800 dark:text-white">মাসিক মোট খরচ</span>
                        <span class="text-2xl font-bold text-red-500">৭০,০০০ ৳ <span class="text-sm text-gray-500">/মাস</span></span>
                    </div>
                </div>

                <!-- AI System -->
                <div class="bg-white dark:bg-gray-800 border-t-4 border-brand-500 shadow-xl rounded-2xl p-8 relative transform md:-translate-y-4">
                    <div class="absolute -top-4 right-6 bg-brand-100 text-brand-600 text-xs font-bold px-3 py-1 rounded-full"><i class="fas fa-star"></i> Smart Choice</div>
                    <h3 class="text-xl font-bold dark:text-white mb-6">NeuralCart AI Platform</h3>
                    
                    <ul class="space-y-4 mb-8 text-gray-600 dark:text-gray-300 text-sm">
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> ১০০% একুরেট রিপ্লাই, কোনো ভুল নেই</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> ১ সেকেন্ডের মধ্যে ইনস্ট্যান্ট রিপ্লাই</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> ২৪/৭ নিরবচ্ছিন্ন কাজ, কোনো ছুটি লাগে না</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> আনলিমিটেড কাস্টমার হ্যান্ডেল করার ক্ষমতা</li>
                    </ul>

                    <div class="bg-brand-50 dark:bg-brand-900/20 p-4 rounded-xl flex justify-between items-center border border-brand-100 dark:border-brand-500/20">
                        <span class="font-bold text-gray-800 dark:text-white">মাসিক মোট খরচ</span>
                        <span class="text-2xl font-bold text-brand-500">৫,০০০ ৳ <span class="text-sm text-gray-500">থেকে শুরু</span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CALL TO ACTION -->
        <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-10 md:p-16 text-center text-white relative flex flex-col items-center overflow-hidden">
            <div class="absolute inset-0 bg-grid-pattern opacity-20"></div>
            <h2 class="text-3xl md:text-5xl font-bold mb-4 z-10 font-bangla">আপনার সেলস বাড়াতে প্রস্তুত?</h2>
            <p class="text-gray-300 md:text-xl mb-10 max-w-2xl z-10 font-bangla">আজই যুক্ত হোন আমাদের প্ল্যাটফর্মে এবং আপনার ই-কমার্স ব্যবসাকে দিন এআই এর শক্তি। প্রথম ৭ দিন সম্পূর্ণ ফ্রি ব্যবহার করে দেখুন!</p>
            <div class="flex flex-col sm:flex-row gap-4 z-10">
                <a href="{{ route('filament.admin.auth.register') }}" class="px-8 py-4 bg-brand-500 hover:bg-brand-600 rounded-xl font-bold text-lg shadow-[0_10px_30px_-10px_rgba(245,48,3,0.5)] transition-all transform hover:-translate-y-1">
                    গেট স্টার্টেড (ফ্রি)
                </a>
                <a href="{{ route('filament.admin.auth.login') }}" class="px-8 py-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl font-bold text-lg transition-all">
                    লগইন করুন
                </a>
            </div>
        </div>

    </div>
</div>
@endsection