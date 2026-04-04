@extends('shop.themes.' . $client->theme_name . '.layout')
@section('title', 'Register - ' . $client->shop_name)

@section('content')
<div class="min-h-[80vh] flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-primary/5"></div>
        <div class="absolute max-w-full -top-32 -right-32 w-64 h-64 bg-primary/10 rounded-full blur-3xl"></div>
        <div class="absolute max-w-full -bottom-32 -left-32 w-64 h-64 bg-primary/10 rounded-full blur-3xl"></div>
    </div>
    
    <div class="max-w-xl w-full space-y-6 bg-white p-8 md:p-10 rounded-2xl shadow-xl relative z-10 border border-gray-100">
        <div>
            <h2 class="text-center text-3xl font-extrabold text-gray-900 tracking-tight">নতুন একাউন্ট খুলুন</h2>
            <p class="mt-2 text-center text-sm text-gray-500">
                অথবা
                <a href="{{ $clean ? $baseUrl.'/login' : route('shop.customer.login', $client->slug) }}" class="font-medium text-primary hover:text-primary transition-colors hover:underline">
                    ইতিমধ্যে একাউন্ট থাকলে লগিন করুন
                </a>
            </p>
        </div>

        <form class="mt-8 relative" action="{{ $clean ? $baseUrl.'/register' : route('shop.customer.register.submit', $client->slug) }}" method="POST">
            @csrf
            
            @if(request()->has('redirect_to'))
                <input type="hidden" name="redirect_to" value="{{ request()->redirect_to }}">
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">আপনার নাম <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="appearance-none block w-full px-4 py-3 border @error('name') border-red-500 @else border-gray-300 @enderror placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm transition-all" placeholder="সম্পূর্ণ নাম লিখুন">
                    @error('name') <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">ফোন নাম্বার <span class="text-red-500">*</span></label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required class="appearance-none block w-full px-4 py-3 border @error('phone') border-red-500 @else border-gray-300 @enderror placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm transition-all" placeholder="01712345678">
                    @error('phone') <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">ইমেইল <span class="text-gray-400 font-normal">(ঐচ্ছিক)</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="appearance-none block w-full px-4 py-3 border @error('email') border-red-500 @else border-gray-300 @enderror placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm transition-all" placeholder="example@email.com">
                    @error('email') <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">পাসওয়ার্ড <span class="text-red-500">*</span></label>
                    <input id="password" name="password" type="password" required class="appearance-none block w-full px-4 py-3 border @error('password') border-red-500 @else border-gray-300 @enderror placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm transition-all" placeholder="কমপক্ষে ৬ অক্ষরের পাসওয়ার্ড">
                    @error('password') <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">পাসওয়ার্ড পুনরায় লিখুন <span class="text-red-500">*</span></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="appearance-none block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm transition-all" placeholder="পাসওয়ার্ডটি আবার লিখুন">
                </div>
            </div>

            <div class="mt-8">
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-lg text-white bg-primary hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all shadow-md hover:shadow-lg">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-white/50 group-hover:text-white/80 transition-colors"></i>
                    </span>
                    একাউন্ট তৈরি করুন
                </button>
            </div>
            
            <p class="text-xs text-center text-gray-500 mt-4">
                একাউন্ট তৈরি করার মাধ্যমে আপনি আমাদের <a href="#" class="text-primary hover:underline">শর্তাবলী</a> এবং <a href="#" class="text-primary hover:underline">প্রাইভেসি পলিসি</a> মেনে নিচ্ছেন।
            </p>
        </form>
    </div>
</div>
@endsection
