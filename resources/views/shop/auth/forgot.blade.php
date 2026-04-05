@extends('shop.themes.' . $client->theme_name . '.layout')
@section('title', 'Forgot Password - ' . $client->shop_name)

@section('content')
<div class="min-h-[70vh] flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-primary/5"></div>
        <div class="absolute max-w-full -top-32 -left-32 w-64 h-64 bg-primary/10 rounded-full blur-3xl"></div>
        <div class="absolute max-w-full -bottom-32 -right-32 w-64 h-64 bg-primary/10 rounded-full blur-3xl"></div>
    </div>
    
    <div class="max-w-md w-full space-y-8 bg-white p-8 md:p-10 rounded-2xl shadow-xl relative z-10 border border-gray-100">
        <div>
            <h2 class="text-center text-3xl font-extrabold text-gray-900 tracking-tight">পাসওয়ার্ড রিকভারি</h2>
            <p class="mt-3 text-center text-sm text-gray-500 leading-relaxed">
                আপনার একাউন্টের ফোন নাম্বার অথবা ইমেইল দিয়ে সাবমিট করুন। আমরা আপনাকে সাপোর্ট হোয়াটসঅ্যাপে রিডাইরেক্ট করে দিব।
            </p>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 font-medium">
                        {{ $errors->first() }}
                    </p>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-amber-700 font-medium">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ $clean ? $baseUrl.'/forgot-password' : route('shop.customer.forgot.submit', $client->slug) }}" method="POST">
            @csrf
            
            <div class="space-y-5 rounded-md shadow-sm">
                <div>
                    <label for="login" class="block text-sm font-semibold text-gray-700 mb-1">ফোন নাম্বার অথবা ইমেইল <span class="text-red-500">*</span></label>
                    <input id="login" name="login" type="text" required class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm transition-all" placeholder="017XXXXXXX অথবা example@email.com">
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-lg text-white bg-primary hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all shadow-md hover:shadow-lg">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fab fa-whatsapp text-white/70 group-hover:text-white transition-colors text-lg"></i>
                    </span>
                    সাপোর্টে পাসওয়ার্ড রিকোয়েস্ট করুন
                </button>
            </div>
            
            <div class="mt-4 text-center">
                <a href="{{ $clean ? $baseUrl.'/login' : route('shop.customer.login', $client->slug) }}" class="font-medium text-gray-500 hover:text-primary transition-colors hover:underline text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i> লগিন পেইজে ফিরে যান
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
