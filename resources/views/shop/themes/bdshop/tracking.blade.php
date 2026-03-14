@extends('shop.themes.bdshop.layout')
@section('title', 'অর্ডার ট্র্যাক | ' . $client->shop_name)

@section('content')
<div class="max-w-[1280px] mx-auto px-4 py-8 sm:py-12">
    
    <div class="max-w-2xl mx-auto text-center mb-10">
        <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex items-center justify-center text-2xl mx-auto mb-4">
            <i class="fas fa-truck-fast"></i>
        </div>
        <h1 class="text-2xl sm:text-4xl font-extrabold text-dark mb-3">অর্ডার ট্র্যাক করুন</h1>
        <p class="text-slate-500 font-medium text-sm sm:text-base">আপনার অর্ডারের মোবাইল নম্বর দিয়ে স্ট্যাটাস দেখুন।</p>
    </div>

    {{-- Search --}}
    <div class="max-w-md mx-auto mb-12">
        <form method="GET" class="flex items-center bg-white border-2 border-slate-200 focus-within:border-primary rounded-xl overflow-hidden transition">
            <div class="pl-4 text-slate-400"><i class="fas fa-mobile-alt"></i></div>
            <input type="text" name="phone" value="{{request('phone')}}" placeholder="01XXXXXXXXX" class="flex-1 bg-transparent border-none py-3.5 text-dark font-bold text-base focus:ring-0 placeholder-slate-400">
            <button type="submit" class="bg-primary text-white px-6 py-3.5 font-bold text-sm uppercase tracking-wider hover:bg-primary/90 transition">ট্র্যাক</button>
        </form>
    </div>

    {{-- Results --}}
    @if(request('phone'))
    <div class="max-w-4xl mx-auto space-y-4">
        <h4 class="font-bold text-dark mb-4 text-center"><span class="text-primary">{{request('phone')}}</span> নম্বরের অর্ডার সমূহ</h4>
        
        @forelse($orders ?? [] as $o)
            <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 hover:border-primary/30 transition">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5 pb-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-500">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">অর্ডার নং</span>
                            <span class="text-xl font-extrabold text-dark">#{{$o->id}}</span>
                        </div>
                    </div>
                    <span class="text-xs font-bold px-4 py-2 rounded-full uppercase tracking-wider
                        @if($o->order_status == 'pending') bg-amber-50 text-amber-600 border border-amber-200
                        @elseif($o->order_status == 'completed') bg-emerald-50 text-emerald-600 border border-emerald-200
                        @elseif($o->order_status == 'cancelled') bg-red-50 text-red-500 border border-red-200
                        @else bg-slate-50 text-slate-600 border border-slate-200 @endif">
                        @if($o->order_status == 'pending') <i class="fas fa-clock mr-1"></i>পেন্ডিং
                        @elseif($o->order_status == 'completed') <i class="fas fa-check mr-1"></i>সম্পন্ন
                        @elseif($o->order_status == 'cancelled') <i class="fas fa-times mr-1"></i>বাতিল
                        @else <i class="fas fa-spinner mr-1"></i>{{$o->order_status}} @endif
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-slate-50 p-3 rounded-lg">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">তারিখ</span>
                        <span class="text-sm font-bold text-dark">{{$o->created_at->format('d M, Y')}}</span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-lg">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">মোট টাকা</span>
                        <span class="text-base font-extrabold text-primary">৳{{number_format($o->total_amount)}}</span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-lg">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">পেমেন্ট</span>
                        <span class="text-xs font-bold text-slate-600">{{$o->payment_status}}</span>
                    </div>
                    @if($o->courier_name)
                    <div class="bg-primary/5 p-3 rounded-lg border border-primary/10">
                        <span class="text-[10px] font-bold text-primary uppercase block mb-1">কুরিয়ার</span>
                        <span class="text-sm font-bold text-dark block">{{$o->courier_name}}</span>
                        <span class="text-[10px] text-slate-500 truncate block">{{$o->tracking_code}}</span>
                    </div>
                    @else
                    <div class="bg-slate-50 p-3 rounded-lg flex items-center justify-center">
                        <span class="text-xs text-slate-400 font-medium">প্রস্তুত হচ্ছে...</span>
                    </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-16 bg-white rounded-xl border border-dashed border-slate-300">
                <i class="fas fa-box-open text-4xl text-slate-300 mb-4"></i>
                <h3 class="text-lg font-bold text-dark mb-1">কোনো অর্ডার পাওয়া যায়নি</h3>
                <p class="text-sm text-slate-500">মোবাইল নম্বরটি পুনরায় চেক করুন।</p>
            </div>
        @endforelse
    </div>
    @endif
</div>
@endsection
