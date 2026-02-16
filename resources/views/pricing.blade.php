<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing Plans</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-slate-800">

    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-gray-900">AsianHost</a>
            
            <nav class="hidden md:flex gap-6 items-center">
                <a href="/" class="text-gray-600 hover:text-gray-900 font-semibold">Home</a>
                <a href="#plans" class="text-gray-600 hover:text-gray-900 font-semibold">Pricing</a>
                
                {{-- 🔥 FIXED: Filament Login Route --}}
                <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 hover:text-gray-900 font-semibold">Login</a>
                
                {{-- 🔥 FIXED: Filament Register Route (Check note below) --}}
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
                <a href="/" class="text-gray-600 font-semibold">Home</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 font-semibold">Login</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="text-blue-600 font-bold">Get Started</a>
            </div>
        </div>
    </header>

    <main class="py-20 px-4" id="plans">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4">Choose Your Perfect Plan</h1>
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

                {{-- 🔥 FIXED: Register Route with Plan Parameter --}}
                <a href="{{ route('filament.admin.auth.register') }}?plan={{ $plan->id }}" 
                   class="w-full block text-center py-4 rounded-xl font-bold transition transform active:scale-95"
                   style="background-color: {{ $plan->color ?? '#2563eb' }}; color: white; box-shadow: 0 4px 14px 0 {{ $plan->color }}66;">
                    Choose {{ $plan->name }}
                </a>
            </div>
            @endforeach
        </div>
    </main>

    <footer class="bg-gray-900 text-white py-12 text-center">
        <p class="opacity-50">&copy; {{ date('Y') }} AsianHost. All rights reserved.</p>
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