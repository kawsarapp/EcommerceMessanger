<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>NeuralCart · Automate Your F-Commerce Sales</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Instrument Sans', 'sans-serif'],
                        bangla: ['Hind Siliguri', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fff2f2',
                            100: '#ffe1e1',
                            500: '#F53003',
                            600: '#d92902',
                            900: '#1a0500',
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Styles */
        .gradient-text {
            background: linear-gradient(135deg, #F53003 0%, #FF750F 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .dark .glass-card {
            background: rgba(22, 22, 21, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        /* Hide scrollbar for clean look */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        ::-webkit-scrollbar-thumb {
            background: #ccc; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #F53003; 
        }
    </style>
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] font-sans antialiased selection:bg-brand-500 selection:text-white">

    <header class="fixed w-full top-0 z-50 transition-all duration-300 bg-white/80 dark:bg-[#0a0a0a]/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <a href="#" class="flex items-center gap-2 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-brand-500 to-orange-500 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg group-hover:rotate-12 transition-transform">N</div>
                    <span class="text-2xl font-bold tracking-tight">Neural<span class="text-brand-500">Cart</span></span>
                </a>

                <nav class="hidden md:flex gap-8 items-center font-medium text-sm text-gray-600 dark:text-gray-300">
                    <a href="#features" class="hover:text-brand-500 transition">Features</a>
                    <a href="#comparison" class="hover:text-brand-500 transition">Savings Calculator</a>
                    <a href="#pricing" class="hover:text-brand-500 transition">Pricing</a>
                    <a href="https://asianhost.net/login" class="px-5 py-2.5 rounded-full bg-black dark:bg-white text-white dark:text-black hover:bg-brand-500 hover:text-white dark:hover:bg-brand-500 transition-all shadow-md">
                        Login Dashboard
                    </a>
                </nav>

                <button class="md:hidden text-2xl text-gray-600">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="pt-24">
        
        <section class="relative overflow-hidden pt-10 pb-20 lg:pt-20 lg:pb-32">
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl animate-pulse-slow"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-red-500/10 rounded-full blur-3xl"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                    
                    <div class="space-y-8 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-50 dark:bg-brand-900/30 border border-brand-100 dark:border-brand-900 text-brand-600 dark:text-brand-400 text-sm font-semibold font-bangla">
                            <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                            বাংলাদেশে এই প্রথম - Next Gen AI Sales
                        </div>
                        
                        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold leading-[1.1] tracking-tight">
                            আপনার বিজনেসকে করুন <br>
                            <span class="gradient-text">Automated Machine</span>
                        </h1>
                        
                        <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto lg:mx-0 font-bangla leading-relaxed">
                            ২৪/৭ কাস্টমার সাপোর্ট, অটো অর্ডার কনফার্মেশন এবং নির্ভুল ইনভেন্টরি ম্যানেজমেন্ট। 
                            মানুষ ঘুমালেও, আপনার <span class="text-brand-500 font-bold">NeuralCart AI</span> ঘুমাবে না।
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-4">
                            <a href="#pricing" class="px-8 py-4 bg-brand-500 hover:bg-brand-600 text-white rounded-full font-bold text-lg shadow-xl shadow-brand-500/30 hover:-translate-y-1 transition-all flex items-center justify-center gap-2">
                                🚀 Start Free Trial <i class="fas fa-arrow-right text-sm"></i>
                            </a>
                            <a href="#comparison" class="px-8 py-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-full font-bold text-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all flex items-center justify-center gap-2 font-bangla">
                                💰 খরচের হিসাব দেখুন
                            </a>
                        </div>

                        <div class="pt-6 flex items-center justify-center lg:justify-start gap-4 text-sm text-gray-500 font-medium">
                            <span class="flex items-center gap-1"><i class="fas fa-check text-green-500"></i> No Credit Card</span>
                            <span class="flex items-center gap-1"><i class="fas fa-check text-green-500"></i> Instant Setup</span>
                        </div>
                    </div>

                    <div class="relative lg:h-[600px] flex items-center justify-center animate-float">
                        <div class="relative w-full max-w-md glass-card rounded-3xl p-6 shadow-2xl border-t border-white/50">
                            <div class="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                        <img src="https://ui-avatars.com/api/?name=Customer&background=random" alt="User">
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-sm">Sharmin Akter</h3>
                                        <p class="text-xs text-green-500 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Online
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs font-mono text-gray-400">12:42 PM</span>
                            </div>

                            <div class="space-y-4 font-bangla text-sm">
                                <div class="flex gap-3">
                                    <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-2xl rounded-tl-none max-w-[80%]">
                                        ভাইয়া, এই নীল শাড়িটার দাম কত? স্টকে আছে?
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-xs text-brand-500 font-medium pl-2">
                                    <i class="fas fa-bolt animate-pulse"></i> AI Checking Stock...
                                </div>

                                <div class="flex gap-3 justify-end">
                                    <div class="bg-gradient-to-r from-brand-500 to-orange-500 text-white p-3 rounded-2xl rounded-tr-none max-w-[90%] shadow-lg">
                                        জি ম্যাম! 😍 এই শাড়িটি আমাদের স্টকে আছে। <br>
                                        দাম: ১,২৫০ টাকা। <br>
                                        সাথে পাচ্ছেন <span class="font-bold bg-white/20 px-1 rounded">ফ্রি ডেলিভারি</span>। অর্ডার কনফার্ম করতে "Order Now" বাটনে ক্লিক করুন! 👇
                                    </div>
                                </div>
                                
                                <div class="flex justify-end">
                                    <div class="bg-white dark:bg-gray-800 p-2 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm w-48">
                                        <div class="h-24 bg-gray-200 rounded-lg mb-2 relative overflow-hidden">
                                           <div class="absolute inset-0 bg-gray-300 animate-pulse"></div> 
                                        </div>
                                        <button class="w-full py-1.5 bg-black dark:bg-white text-white dark:text-black text-xs font-bold rounded-lg">
                                            Confirm Order
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="absolute -right-4 top-20 bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 animate-[bounce_3s_infinite]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xl">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Today's Sales</p>
                                    <p class="text-lg font-bold">৳ ২৫,৪০০</p>
                                </div>
                            </div>
                        </div>

                        <div class="absolute -left-8 bottom-32 bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">AI Replied</p>
                                    <p class="text-lg font-bold">1,240 Msgs</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-20 bg-white dark:bg-[#0a0a0a] border-t border-gray-100 dark:border-gray-900">
            <div class="max-w-7xl mx-auto px-4 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-12 font-bangla">
                    কেন ম্যানুয়াল সিস্টেমে আপনি <span class="text-red-500">পিছিয়ে পড়ছেন?</span>
                </h2>
                
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="p-8 rounded-3xl bg-gray-50 dark:bg-[#111] hover:bg-red-50 dark:hover:bg-red-900/10 transition duration-300 group text-left">
                        <div class="w-14 h-14 bg-red-100 text-red-500 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 font-bangla">স্লো রেসপন্স = সেল লস</h3>
                        <p class="text-gray-500 font-bangla leading-relaxed">
                            আপনি যখন ঘুমাচ্ছেন বা ব্যস্ত, তখন কাস্টমার মেসেজ দিচ্ছে। ১ ঘণ্টা পর রিপ্লাই দিলে সেই কাস্টমার আর থাকে না, চলে যায় অন্য পেজে।
                        </p>
                    </div>

                    <div class="p-8 rounded-3xl bg-gray-50 dark:bg-[#111] hover:bg-red-50 dark:hover:bg-red-900/10 transition duration-300 group text-left">
                        <div class="w-14 h-14 bg-red-100 text-red-500 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 font-bangla">অতিরিক্ত স্টাফ খরচ</h3>
                        <p class="text-gray-500 font-bangla leading-relaxed">
                            ২৪ ঘণ্টা সাপোর্ট দিতে গেলে ৩ শিফটে মানুষ লাগে। বেতন, বোনাস, ইন্টারনেট খরচ মিলিয়ে আপনার প্রফিটের অর্ধেক চলে যায় স্টাফ খরচে।
                        </p>
                    </div>

                    <div class="p-8 rounded-3xl bg-gray-50 dark:bg-[#111] hover:bg-red-50 dark:hover:bg-red-900/10 transition duration-300 group text-left">
                        <div class="w-14 h-14 bg-red-100 text-red-500 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 font-bangla">ভুল অর্ডার ও ফ্রড</h3>
                        <p class="text-gray-500 font-bangla leading-relaxed">
                            মানুষের ভুলে ভুল প্রোডাক্ট ডেলিভারি হয়। এছাড়া ফেইক অর্ডার আইডেন্টিফাই করতে না পারায় ডেলিভারি চার্জ লস হয়।
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="comparison" class="py-24 bg-gray-900 text-white relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden opacity-20 pointer-events-none">
                <div class="absolute top-10 left-10 w-72 h-72 bg-blue-500 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-10 right-10 w-96 h-96 bg-brand-500 rounded-full blur-[120px]"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="text-center mb-16">
                    <span class="text-brand-400 font-bold tracking-wider uppercase text-sm">Real Data Analysis</span>
                    <h2 class="text-3xl md:text-5xl font-bold mt-2 mb-6 font-bangla">ম্যানুয়াল টিম vs AI সিস্টেম</h2>
                    <p class="text-gray-400 max-w-2xl mx-auto font-bangla text-lg">
                        ধরি, আপনার টার্গেট প্রতিদিন **৫০০টি অর্ডার**। এই অপারেশন চালাতে আপনার খরচের পার্থক্য দেখুন।
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
                    
                    <div class="bg-white/5 backdrop-blur-sm rounded-3xl p-8 border border-white/10 relative">
                        <h3 class="text-2xl font-bold text-red-400 mb-2 flex items-center gap-3">
                            <i class="fas fa-users"></i> Manual Human Team
                        </h3>
                        <p class="text-gray-400 text-sm mb-8 border-b border-gray-700 pb-4">১৫ জন মডারেটর (৩ শিফট)</p>

                        <div class="space-y-6 font-bangla">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-300">স্টাফ স্যালারি (১৫ জন)</span>
                                <span class="font-bold text-xl">১,৫০,০০০ ৳</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-300">শিফট, নাস্তা ও বিল</span>
                                <span class="font-bold text-xl">৮০,০০০ ৳</span>
                            </div>
                            <div class="flex justify-between items-center text-red-400">
                                <span class="">হিউম্যান এরর (Loss)</span>
                                <span class="font-bold text-xl">+২০,০০০ ৳</span>
                            </div>
                        </div>

                        <div class="mt-10 pt-6 border-t border-gray-700">
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-sm text-gray-500 uppercase">Total Monthly Cost</p>
                                    <p class="text-3xl font-bold text-white">৳ ২,৫০,০০০</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500 uppercase">Yearly Loss</p>
                                    <p class="text-xl font-bold text-red-500">৳ ৩০,০০,০০০</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-b from-brand-900 to-black rounded-3xl p-8 border border-brand-500/50 shadow-2xl relative overflow-hidden transform md:-translate-y-4">
                        <div class="absolute top-0 right-0 bg-brand-600 text-white text-xs font-bold px-4 py-1.5 rounded-bl-xl">BEST CHOICE</div>
                        
                        <h3 class="text-2xl font-bold text-brand-400 mb-2 flex items-center gap-3">
                            <i class="fas fa-robot"></i> NeuralCart AI
                        </h3>
                        <p class="text-gray-400 text-sm mb-8 border-b border-gray-800 pb-4">Full Automation (24/7)</p>

                        <div class="space-y-6 font-bangla">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-300">স্যালারি ও বোনাস</span>
                                <span class="font-bold text-xl text-green-400">০ ৳ (জিরো)</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-300">ক্যাপাসিটি</span>
                                <span class="font-bold text-xl">আনলিমিটেড</span>
                            </div>
                            <div class="flex justify-between items-center text-brand-400">
                                <span class="">সফটওয়্যার খরচ (ফিক্সড)</span>
                                <span class="font-bold text-xl">সামান্য*</span>
                            </div>
                        </div>

                        <div class="mt-10 pt-6 border-t border-gray-800">
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-sm text-gray-500 uppercase">Total Monthly Cost</p>
                                    <p class="text-3xl font-bold text-white">৳ ৫,০০০ - ১০,০০০</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500 uppercase">Total Savings</p>
                                    <p class="text-2xl font-bold text-green-400">৯৬% সাশ্রয়!</p>
                                </div>
                            </div>
                            <a href="#pricing" class="block mt-6 w-full py-4 bg-brand-600 hover:bg-brand-500 text-white text-center rounded-xl font-bold transition">
                                Get Started Now
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <section id="features" class="py-24 bg-[#FDFDFC] dark:bg-[#0a0a0a]">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold mb-4 font-bangla">Core AI Features</h2>
                    <p class="text-gray-500 dark:text-gray-400">সবকিছু এক প্ল্যাটফর্মে। আলাদা কোনো টুলের প্রয়োজন নেই।</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="group p-8 rounded-[2rem] bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#333] hover:border-brand-500/50 hover:shadow-2xl hover:shadow-brand-500/10 transition-all duration-300">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-xl flex items-center justify-center text-xl mb-6">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 font-bangla">Instant Reply (0 Sec)</h3>
                        <p class="text-gray-500 dark:text-gray-400 font-bangla text-sm leading-relaxed">
                            কাস্টমার হাই দেওয়া মাত্রই রিপ্লাই। প্রোডাক্টের দাম, সাইজ, ছবি—সব অটোমেটিক সেন্ড করবে।
                        </p>
                    </div>

                    <div class="group p-8 rounded-[2rem] bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#333] hover:border-brand-500/50 hover:shadow-2xl hover:shadow-brand-500/10 transition-all duration-300">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 text-purple-600 rounded-xl flex items-center justify-center text-xl mb-6">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 font-bangla">Smart Inventory</h3>
                        <p class="text-gray-500 dark:text-gray-400 font-bangla text-sm leading-relaxed">
                            স্টকে পণ্য না থাকলে অর্ডার নিবে না, বরং "Restock Alert" সেট করবে। আপনার ম্যানুয়াল চেক করার দরকার নেই।
                        </p>
                    </div>

                    <div class="group p-8 rounded-[2rem] bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#333] hover:border-brand-500/50 hover:shadow-2xl hover:shadow-brand-500/10 transition-all duration-300">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 text-green-600 rounded-xl flex items-center justify-center text-xl mb-6">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 font-bangla">Fraud Detection</h3>
                        <p class="text-gray-500 dark:text-gray-400 font-bangla text-sm leading-relaxed">
                            যারা আগে অর্ডার করে পণ্য নেয়নি, তাদের চিনে রাখবে এবং আপনাকে সতর্ক করবে। ডেলিভারি চার্জ লস হবে না।
                        </p>
                    </div>

                    <div class="group p-8 rounded-[2rem] bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#333] hover:border-brand-500/50 hover:shadow-2xl hover:shadow-brand-500/10 transition-all duration-300">
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 text-orange-600 rounded-xl flex items-center justify-center text-xl mb-6">
                            <i class="fas fa-camera"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 font-bangla">Visual Search</h3>
                        <p class="text-gray-500 dark:text-gray-400 font-bangla text-sm leading-relaxed">
                            কাস্টমার কোনো জামার ছবি দিলে AI সেটা দেখে আপনার স্টকের সাথে মিলিয়ে বের করে দিবে।
                        </p>
                    </div>

                    <div class="group p-8 rounded-[2rem] bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#333] hover:border-brand-500/50 hover:shadow-2xl hover:shadow-brand-500/10 transition-all duration-300">
                        <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/30 text-pink-600 rounded-xl flex items-center justify-center text-xl mb-6">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 font-bangla">Human Psychology</h3>
                        <p class="text-gray-500 dark:text-gray-400 font-bangla text-sm leading-relaxed">
                            রোবটের মতো নয়, মানুষের মতো কথা বলে। কাস্টমার দাম বেশি বললে কনভেন্স করে সেল ক্লোজ করে।
                        </p>
                    </div>

                    <div class="group p-8 rounded-[2rem] bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#333] hover:border-brand-500/50 hover:shadow-2xl hover:shadow-brand-500/10 transition-all duration-300">
                        <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 rounded-xl flex items-center justify-center text-xl mb-6">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 font-bangla">Daily Report</h3>
                        <p class="text-gray-500 dark:text-gray-400 font-bangla text-sm leading-relaxed">
                            দিন শেষে টেলিগ্রামে রিপোর্ট পাঠাবে—কত টাকা সেল হলো, কতটি অর্ডার পেন্ডিং।
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-10 px-4">
            <div class="max-w-7xl mx-auto rounded-3xl bg-brand-500 text-white p-10 md:p-16 text-center relative overflow-hidden">
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
                <div class="relative z-10">
                    <h2 class="text-3xl md:text-5xl font-bold mb-6 font-bangla">ব্যবসা বড় করতে প্রস্তুত?</h2>
                    <p class="text-lg opacity-90 mb-10 max-w-2xl mx-auto font-bangla">
                        আর দেরি করবেন না। আজই ফ্রি ট্রায়াল শুরু করুন এবং দেখুন AI কিভাবে আপনার সেল দ্বিগুণ করে।
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="#pricing" class="bg-white text-brand-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-gray-100 transition shadow-lg">
                            Get Started Free
                        </a>
                        <a href="tel:01771545972" class="bg-brand-700 text-white border border-brand-400 px-8 py-4 rounded-full font-bold text-lg hover:bg-brand-800 transition">
                            Call Us: 01771545972
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-gray-50 dark:bg-[#111] pt-20 pb-10 border-t border-gray-200 dark:border-gray-800 mt-20">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-12 mb-16">
                <div class="col-span-1 md:col-span-2">
                    <a href="#" class="flex items-center gap-2 mb-6">
                        <div class="w-8 h-8 bg-brand-500 rounded-lg flex items-center justify-center text-white font-bold">N</div>
                        <span class="text-xl font-bold dark:text-white">NeuralCart</span>
                    </a>
                    <p class="text-gray-500 dark:text-gray-400 mb-6 font-bangla max-w-sm">
                        বাংলাদেশের ক্ষুদ্র ও মাঝারি উদ্যোক্তাদের জন্য তৈরি #১ AI সেলস অ্যাসিস্ট্যান্ট। আমরা প্রযুক্তির মাধ্যমে আপনার ব্যবসাকে সহজ করি।
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 hover:text-brand-500 hover:border-brand-500 transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 hover:text-brand-500 hover:border-brand-500 transition"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white mb-6">Product</h4>
                    <ul class="space-y-4 text-sm text-gray-500 dark:text-gray-400">
                        <li><a href="#" class="hover:text-brand-500">Features</a></li>
                        <li><a href="#" class="hover:text-brand-500">Pricing</a></li>
                        <li><a href="#" class="hover:text-brand-500">API Docs</a></li>
                        <li><a href="#" class="hover:text-brand-500">Integrations</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white mb-6">Company</h4>
                    <ul class="space-y-4 text-sm text-gray-500 dark:text-gray-400">
                        <li><a href="#" class="hover:text-brand-500">About Us</a></li>
                        <li><a href="#" class="hover:text-brand-500">Contact</a></li>
                        <li><a href="#" class="hover:text-brand-500">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-brand-500">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-gray-200 dark:border-gray-800 text-center text-gray-400 text-sm">
                <p>&copy; 2026 NeuralCart AI. Developed by <span class="text-brand-500">Kawsar Ahmed</span>.</p>
            </div>
        </div>
    </footer>

</body>
</html>