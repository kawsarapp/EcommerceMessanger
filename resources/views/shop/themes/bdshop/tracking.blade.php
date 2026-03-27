@extends('shop.themes.bdshop.layout')
@section('title', 'অর্ডার ট্র্যাক | ' . $client->shop_name)

@section('content')
<div class="max-w-7xl mx-auto px-3 sm:px-4 py-6 sm:py-10">

    {{-- Header Section --}}
    <div class="max-w-2xl mx-auto text-center mb-8 sm:mb-10">
        <div class="w-16 h-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4">
            <i class="fas fa-truck-fast"></i>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-dark mb-3">অর্ডার ট্র্যাক করুন</h1>
        <p class="text-slate-500 font-medium text-sm sm:text-base">আপনার অর্ডারের মোবাইল নম্বর দিয়ে স্ট্যাটাস দেখুন।</p>
    </div>

    {{-- Search Box --}}
    <div class="max-w-md mx-auto mb-10">
        <form method="GET" class="relative">
            <div class="flex items-center bg-white border-2 border-slate-200 focus-within:border-primary rounded-xl overflow-hidden transition shadow-sm">
                <div class="pl-4 text-slate-400">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <input type="text" name="phone" value="{{ request('phone') }}" placeholder="01XXXXXXXXX"
                    class="flex-1 bg-transparent border-none py-4 text-dark font-bold text-base focus:ring-0 placeholder-slate-400">
                <button type="submit" class="bg-primary text-white px-6 py-4 font-bold text-sm uppercase tracking-wider hover:bg-primary/90 transition">
                    <i class="fas fa-search mr-2"></i>ট্র্যাক
                </button>
            </div>
        </form>

        {{-- Helper Text --}}
        <p class="text-xs text-slate-400 text-center mt-3">
            <i class="fas fa-info-circle mr-1"></i> অর্ডার করার সময় যে নম্বর দিয়েছিলেন সেটি লিখুন
        </p>
    </div>

    {{-- Results Section --}}
    @if(request('phone'))
    <div class="max-w-4xl mx-auto">
        <h4 class="font-bold text-dark mb-4 text-center text-lg">
            <span class="text-primary bg-primary/10 px-3 py-1 rounded-lg">{{ request('phone') }}</span> নম্বরের অর্ডার সমূহ
        </h4>

        @forelse($orders ?? [] as $o)
            <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 hover:border-primary/30 hover:shadow-lg transition mb-4">
                {{-- Order Header --}}
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5 pb-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                            <i class="fas fa-receipt text-lg"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">অর্ডার নং</span>
                            <span class="text-2xl font-bold text-dark">#{{ $o->id }}</span>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <span class="text-xs font-bold px-4 py-2 rounded-full uppercase tracking-wider
                        @if($o->order_status == 'pending') bg-amber-50 text-amber-600 border border-amber-200
                        @elseif($o->order_status == 'processing') bg-blue-50 text-blue-600 border border-blue-200
                        @elseif($o->order_status == 'shipped') bg-purple-50 text-purple-600 border border-purple-200
                        @elseif($o->order_status == 'completed') bg-emerald-50 text-emerald-600 border border-emerald-200
                        @elseif($o->order_status == 'cancelled') bg-red-50 text-red-500 border border-red-200
                        @else bg-slate-50 text-slate-600 border border-slate-200 @endif">
                        @if($o->order_status == 'pending') <i class="fas fa-clock mr-1"></i>পেন্ডিং
                        @elseif($o->order_status == 'processing') <i class="fas fa-cog mr-1"></i>প্রসেসিং
                        @elseif($o->order_status == 'shipped') <i class="fas fa-truck mr-1"></i>শিপড
                        @elseif($o->order_status == 'completed') <i class="fas fa-check mr-1"></i>সম্পন্ন
                        @elseif($o->order_status == 'cancelled') <i class="fas fa-times mr-1"></i>বাতিল
                        @else <i class="fas fa-spinner mr-1"></i>{{ $o->order_status }} @endif
                    </span>
                </div>

                {{-- Order Details Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-slate-50 p-4 rounded-xl">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">তারিখ</span>
                        <span class="text-sm font-bold text-dark">{{ $o->created_at->format('d M, Y') }}</span>
                        <span class="text-xs text-slate-400 block">{{ $o->created_at->format('h:i A') }}</span>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">মোট টাকা</span>
                        <span class="text-xl font-bold text-primary">৳{{ number_format($o->total_amount) }}</span>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">পেমেন্ট</span>
                        <span class="text-xs font-bold text-slate-600 {{ $o->payment_status == 'paid' ? 'text-emerald-600' : '' }}">{{ $o->payment_status }}</span>
                    </div>
                    @if($o->courier_name)
                    <div class="bg-primary/5 p-4 rounded-xl border border-primary/20">
                        <span class="text-[10px] font-bold text-primary uppercase block mb-1">কুরিয়ার</span>
                        <span class="text-sm font-bold text-dark block">{{ $o->courier_name }}</span>
                        @if($o->tracking_code)
                        <span class="text-[10px] text-slate-500 truncate block">{{ $o->tracking_code }}</span>
                        @endif
                    </div>
                    @else
                    <div class="bg-slate-50 p-4 rounded-xl flex items-center justify-center">
                        <span class="text-xs text-slate-400 font-medium">প্রস্তুত হচ্ছে...</span>
                    </div>
                    @endif
                </div>

                {{-- Progress Bar (for shipped orders) --}}
                @if($o->order_status == 'shipped')
                <div class="mt-5 pt-5 border-t border-slate-100">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-400 mb-2">
                        <span>অর্ডার কনফার্ম</span>
                        <span>প্রসেসিং</span>
                        <span class="text-primary">শিপড</span>
                        <span>ডেলিভারি</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-primary to-orange-400 rounded-full" style="width: 75%"></div>
                    </div>
                </div>
                @endif
            </div>
        @empty
            <div class="text-center py-16 bg-white rounded-xl border-2 border-dashed border-slate-300">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-box-open text-3xl text-slate-300"></i>
                </div>
                <h3 class="text-lg font-bold text-dark mb-2">কোনো অর্ডার পাওয়া যায়নি</h3>
                <p class="text-sm text-slate-500 mb-4">মোবাইল নম্বরটি পুনরায় চেক করুন।</p>
                <a href="{{ $baseUrl }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary/90 transition">
                    <i class="fas fa-shopping-bag"></i> শপিং করুন
                </a>
            </div>
        @endforelse
    </div>
    @endif
</div>
@endsection
