@extends('shop.themes.' . $client->theme_name . '.layout')
@section('title', 'Login - ' . $client->shop_name)

@section('content')
<div class="min-h-[70vh] flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-primary/5"></div>
        <div class="absolute max-w-full -top-32 -left-32 w-64 h-64 bg-primary/10 rounded-full blur-3xl"></div>
        <div class="absolute max-w-full -bottom-32 -right-32 w-64 h-64 bg-primary/10 rounded-full blur-3xl"></div>
    </div>
    
    <div class="max-w-md w-full space-y-8 bg-white p-8 md:p-10 rounded-2xl shadow-xl relative z-10 border border-gray-100">
        <div>
            <h2 class="text-center text-3xl font-extrabold text-gray-900 tracking-tight">আপনার একাউন্টে লগিন করুন</h2>
            <p class="mt-2 text-center text-sm text-gray-500">
                অথবা
                <a href="{{ $clean ? $baseUrl.'/register' : route('shop.customer.register', $client->slug) }}" class="font-medium text-primary hover:text-primary transition-colors hover:underline">
                    নতুন একটি একাউন্ট তৈরি করুন
                </a>
            </p>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 font-medium">
                        {{ $errors->first() }}
                    </p>
                </div>
            </div>
        </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ $clean ? $baseUrl.'/login' : route('shop.customer.login.submit', $client->slug) }}" method="POST">
            @csrf
            
            @if(request()->has('redirect_to'))
                <input type="hidden" name="redirect_to" value="{{ request()->redirect_to }}">
            @endif

            <div class="space-y-5 rounded-md shadow-sm">
                <div>
                    <label for="login" class="block text-sm font-semibold text-gray-700 mb-1">ফোন নাম্বার অথবা ইমেইল <span class="text-red-500">*</span></label>
                    <input id="login" name="login" type="text" required class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm transition-all" placeholder="017XXXXXXX অথবা example@email.com">
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">পাসওয়ার্ড <span class="text-red-500">*</span></label>
                    <input id="password" name="password" type="password" required class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm transition-all" placeholder="পাসওয়ার্ড লিখুন">
                </div>
            </div>

            <div class="flex items-center justify-between mt-4">
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded cursor-pointer">
                    <label for="remember" class="ml-2 block text-sm text-gray-900 cursor-pointer select-none">
                        আমাকে মনে রাখুন
                    </label>
                </div>

                <div class="text-sm">
                    <a href="#" class="font-medium text-primary hover:underline">
                        পাসওয়ার্ড ভুলে গেছেন?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-lg text-white bg-primary hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all shadow-md hover:shadow-lg">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-lock text-white/50 group-hover:text-white/80 transition-colors"></i>
                    </span>
                    লগিন করুন
                </button>
            </div>
            
            <div class="mt-6">
                <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="w-full flex justify-center py-3 px-4 border border-gray-300 text-sm font-bold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-all shadow-sm">
                    পাসওয়ার্ড ছাড়া অর্ডার ট্র্যাক করুন
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
