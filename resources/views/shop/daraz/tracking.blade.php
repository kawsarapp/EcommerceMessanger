@extends('shop.daraz.layout')
@section('title', 'অর্ডার ট্র্যাক | ' . $client->shop_name)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 md:py-12">
    
    {{-- Header --}}
    <div class="text-center mb-10">
        <div class="w-16 h-16 hero-gradient rounded-2xl flex items-center justify-center text-white text-3xl mx-auto mb-4">
            <i class="fas fa-truck-fast"></i>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-dark mb-2">অর্ডার ট্র্যাক করুন</h1>
        <p class="text-gray-500 text-sm">আপনার অর্ডারের মোবাইল নম্বর দিয়ে স্ট্যাটাস দেখুন।</p>
    </div>

    {{-- Search Box --}}
    <div class="max-w-md mx-auto mb-12">
        <form method="GET">
            <div class="flex bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="pl-4 text-gray-400 flex items-center"><i class="fas fa-mobile-alt"></i></div>
                <input type="text" name="phone" value="{{ request('phone') }}" placeholder="01XXXXXXXXX"
                    class="flex-1 bg-transparent border-none py-4 px-3 text-dark font-bold focus:ring-0 placeholder-gray-400">
                <button type="submit" class="btn-primary px-6 text-white font-bold text-sm uppercase flex items-center gap-2 transition">
                    <i class="fas fa-search"></i> ট্র্যাক
                </button>
            </div>
        </form>
        <p class="text-xs text-gray-400 text-center mt-3"><i class="fas fa-info-circle mr-1"></i> অর্ডার করার সময় যে নম্বর দিয়েছিলেন</p>
    </div>

    {{-- Results --}}
    @if(request('phone'))
    <div>
        <h4 class="text-center mb-6">
            <span class="bg-primary/10 text-primary px-4 py-1.5 rounded-full text-sm font-bold">{{ request('phone') }}</span>
            <span class="text-gray-500 text-sm ml-2">নম্বরের অর্ডার সমূহ</span>
        </h4>

        @forelse($orders ?? [] as $o)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6 mb-4 hover:shadow-md transition">
                {{-- Header --}}
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5 pb-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                            <i class="fas fa-receipt text-lg"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">অর্ডার নং</span>
                            <span class="text-2xl font-bold text-dark">#{{ $o->id }}</span>
                        </div>
                    </div>
                    <span class="text-xs font-bold px-4 py-2 rounded-full uppercase tracking-wider
                        @if($o->order_status=='pending') bg-amber-50 text-amber-600 border border-amber-200
                        @elseif($o->order_status=='processing') bg-blue-50 text-blue-600 border border-blue-200
                        @elseif($o->order_status=='shipped') bg-purple-50 text-purple-600 border border-purple-200
                        @elseif($o->order_status=='completed') bg-green-50 text-green-600 border border-green-200
                        @elseif($o->order_status=='cancelled') bg-red-50 text-red-500 border border-red-200
                        @else bg-gray-50 text-gray-600 border border-gray-200 @endif">
                        @if($o->order_status=='pending') <i class="fas fa-clock mr-1"></i>পেন্ডিং
                        @elseif($o->order_status=='processing') <i class="fas fa-cog mr-1"></i>প্রসেসিং
                        @elseif($o->order_status=='shipped') <i class="fas fa-truck mr-1"></i>শিপড
                        @elseif($o->order_status=='completed') <i class="fas fa-check mr-1"></i>সম্পন্ন
                        @elseif($o->order_status=='cancelled') <i class="fas fa-times mr-1"></i>বাতিল
                        @else <i class="fas fa-spinner mr-1"></i>{{ $o->order_status }} @endif
                    </span>
                </div>

                {{-- Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-gray-50 p-3 rounded-xl">
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">তারিখ</span>
                        <span class="text-sm font-bold text-dark block">{{ $o->created_at->format('d M, Y') }}</span>
                        <span class="text-xs text-gray-400">{{ $o->created_at->format('h:i A') }}</span>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-xl">
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">মোট টাকা</span>
                        <span class="text-lg font-bold text-primary">৳{{ number_format($o->total_amount) }}</span>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-xl">
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">পেমেন্ট</span>
                        <span class="text-xs font-bold {{ $o->payment_status=='paid' ? 'text-green-600' : 'text-gray-600' }}">{{ $o->payment_status }}</span>
                    </div>
                    @if($o->courier_name)
                    <div class="bg-primary/5 p-3 rounded-xl border border-primary/10">
                        <span class="text-[10px] font-bold text-primary uppercase block mb-1">কুরিয়ার</span>
                        <span class="text-sm font-bold text-dark block">{{ $o->courier_name }}</span>
                        @if($o->tracking_code)<span class="text-[10px] text-gray-500 truncate block">{{ $o->tracking_code }}</span>@endif
                    </div>
                    @else
                    <div class="bg-gray-50 p-3 rounded-xl flex items-center justify-center">
                        <span class="text-xs text-gray-400 font-medium">প্রস্তুত হচ্ছে...</span>
                    </div>
                    @endif
                </div>

                {{-- Progress --}}
                @if($o->order_status == 'shipped')
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <div class="flex justify-between text-xs font-bold text-gray-400 mb-2">
                        <span>অর্ডার</span><span>প্রসেসিং</span><span class="text-primary">শিপড</span><span>ডেলিভারি</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full hero-gradient rounded-full" style="width:75%"></div>
                    </div>
                </div>
                @endif
            </div>
        @empty
            <div class="text-center py-16 bg-white rounded-2xl border-2 border-dashed border-gray-200">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-box-open text-3xl text-gray-300"></i>
                </div>
                <h3 class="text-lg font-bold text-dark mb-2">কোনো অর্ডার পাওয়া যায়নি</h3>
                <p class="text-sm text-gray-500 mb-6">মোবাইল নম্বরটি পুনরায় চেক করুন।</p>
                <a href="{{ $baseUrl }}" class="btn-primary inline-flex items-center gap-2 px-6 py-3 text-white rounded-xl font-semibold text-sm transition">
                    <i class="fas fa-shopping-bag"></i> শপিং করুন
                </a>
            </div>
        @endforelse
    </div>
    @endif
</div>
@endsection
