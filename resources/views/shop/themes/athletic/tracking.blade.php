@extends('shop.themes.athletic.layout')
@section('title', 'অর্ডার ট্র্যাক করুন | ' . $client->shop_name)

@section('content')
@php
$baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug);
@endphp
<div class="max-w-[70rem] mx-auto px-4 sm:px-8 py-16 md:py-24">
    
    <!-- Section Header -->
    <div class="text-center mb-16 relative">
        <div class="absolute inset-0 flex items-center justify-center -z-10 opacity-5">
            <i class="fas fa-map-marked-alt text-[20rem] text-dark"></i>
        </div>
        <div class="w-32 h-32 bg-dark mx-auto mb-8 flex items-center justify-center text-primary text-6xl shadow-primary-lg -skew-x-[8deg] border-4 border-dark">
            <i class="fas fa-truck skew-x-[8deg]"></i>
        </div>
        <h1 class="text-6xl md:text-8xl font-display font-bold text-dark mb-4 uppercase tracking-tighter mix-blend-multiply">অর্ডার ট্র্যাক করুন</h1>
        <p class="font-sans font-bold text-lg text-gray-500 max-w-lg mx-auto uppercase tracking-widest border-t-4 border-dark pt-4">আপনার ফোন নম্বর দিয়ে অর্ডারের অবস্থান জানুন।</p>
    </div>

    <!-- Search Box -->
    <div class="max-w-3xl mx-auto mb-20">
        <form method="GET" class="relative">
            <div class="flex flex-col sm:flex-row bg-white border-[6px] border-dark shadow-primary-xl p-2 md:p-4 -skew-x-[4deg]">
                <div class="pl-6 text-dark flex items-center skew-x-[4deg]"><i class="fas fa-phone text-4xl"></i></div>
                <input type="text" name="phone" value="{{ request('phone') }}" 
                    placeholder="ফোন নম্বর লিখুন (01XXXXXXXXX)"
                    class="flex-1 bg-transparent border-none py-6 px-6 text-dark font-display font-bold text-xl md:text-3xl focus:ring-0 placeholder-gray-300 skew-x-[4deg] outline-none">
                <button type="submit" class="btn-speed bg-primary px-8 md:px-12 py-6 text-white font-display font-bold text-2xl md:text-3xl uppercase tracking-widest transition skew-x-[4deg] border-4 border-dark mt-4 sm:mt-0">
                    <span class="skew-x-[4deg]">খুঁজুন</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Results -->
    @if(request('phone'))
    <div>
        <h4 class="text-center mb-16 pb-8 border-b-8 border-dark">
            <span class="font-display font-bold text-4xl text-gray-400 uppercase tracking-widest block mb-2">এই নম্বরের অর্ডার সমূহ</span>
            <span class="bg-dark text-primary px-8 py-3 text-3xl md:text-5xl font-display font-bold uppercase tracking-widest shadow-primary-md border-4 border-primary -skew-x-[6deg] inline-block">
                <span class="skew-x-[6deg] block">{{ request('phone') }}</span>
            </span>
        </h4>

        <div class="grid grid-cols-1 gap-12">
        @forelse($orders ?? [] as $o)
            <div class="bg-gray-50 border-8 border-dark p-8 md:p-12 hover:-translate-y-2 hover:shadow-primary-xl transition-all duration-300 relative group overflow-hidden">
                
                <div class="absolute -right-12 -top-12 text-[15rem] font-display font-black text-gray-200 select-none z-0 mix-blend-multiply opacity-30 transform group-hover:scale-110 group-hover:-rotate-12 transition-all duration-700">#{{$o->id}}</div>

                <div class="relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 pb-8 border-b-[6px] border-gray-300 gap-6">
                        <div class="flex items-end gap-6">
                            <div class="w-20 h-20 bg-dark text-primary border-4 border-primary flex items-center justify-center text-4xl -skew-x-[8deg] shrink-0">
                                <i class="fas fa-box skew-x-[8deg]"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-display font-bold text-xl text-gray-500 uppercase tracking-widest leading-none mb-2">অর্ডার আইডি</span>
                                <span class="font-display font-black text-5xl md:text-7xl text-dark leading-none">#{{ $o->id }}</span>
                            </div>
                        </div>
                        
                        <!-- Status Badge -->
                        @php
                            $statusMap = [
                                'pending'    => ['label' => 'অপেক্ষমান',    'icon' => 'fa-hourglass-start', 'color' => 'text-yellow-600'],
                                'processing' => ['label' => 'প্রক্রিয়াধীন',  'icon' => 'fa-cog fa-spin',    'color' => 'text-blue-600'],
                                'shipped'    => ['label' => 'পাঠানো হয়েছে', 'icon' => 'fa-plane-departure','color' => 'text-primary'],
                                'completed'  => ['label' => 'ডেলিভারি সম্পন্ন','icon' => 'fa-check-double','color' => 'text-green-600'],
                                'cancelled'  => ['label' => 'বাতিল',         'icon' => 'fa-times-circle',  'color' => 'text-red-600'],
                            ];
                            $st = $statusMap[$o->order_status] ?? ['label' => $o->order_status, 'icon' => 'fa-circle', 'color' => 'text-gray-600'];
                        @endphp
                        <div class="px-8 py-4 border-[6px] text-3xl font-display font-bold uppercase tracking-widest -skew-x-[6deg] bg-white text-dark border-dark shadow-dark-sm">
                            <span class="skew-x-[6deg] block flex items-center gap-4 {{ $st['color'] }}">
                                <i class="fas {{ $st['icon'] }}"></i> {{ $st['label'] }}
                            </span>
                        </div>
                    </div>

                    <!-- Order Info Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                        <div class="bg-white border-4 border-dark p-6">
                            <span class="font-sans font-bold text-xs text-gray-500 uppercase tracking-widest block mb-2 border-b-2 border-gray-200 pb-2">তারিখ</span>
                            <span class="font-display font-bold text-3xl text-dark block leading-none mb-1">{{ $o->created_at->format('d M') }}</span>
                            <span class="font-sans font-bold text-sm text-primary">{{ $o->created_at->format('h:i A') }}</span>
                        </div>
                        <div class="bg-white border-4 border-dark p-6">
                            <span class="font-sans font-bold text-xs text-gray-500 uppercase tracking-widest block mb-2 border-b-2 border-gray-200 pb-2">মোট মূল্য</span>
                            <span class="font-display font-bold text-4xl text-primary block leading-none mt-2">৳{{ number_format($o->total_amount) }}</span>
                        </div>
                        <div class="bg-white border-4 border-dark p-6">
                            <span class="font-sans font-bold text-xs text-gray-500 uppercase tracking-widest block mb-2 border-b-2 border-gray-200 pb-2">পেমেন্ট</span>
                            @php
                                $payColors = ['paid' => 'text-green-600', 'unpaid' => 'text-red-600', 'partial' => 'text-yellow-600'];
                                $payLabels = ['paid' => 'পরিশোধিত', 'unpaid' => 'বাকি', 'partial' => 'আংশিক'];
                            @endphp
                            <span class="font-display font-bold text-3xl uppercase block leading-none mt-2 {{ $payColors[$o->payment_status] ?? 'text-dark' }}">
                                {{ $payLabels[$o->payment_status] ?? $o->payment_status }}
                            </span>
                        </div>
                        
                        @if($o->courier_name)
                        <div class="bg-dark text-white border-4 border-primary p-6 shadow-primary-sm">
                            <span class="font-sans font-bold text-xs text-gray-400 uppercase tracking-widest block mb-2 border-b-2 border-gray-700 pb-2">কুরিয়ার</span>
                            <span class="font-display font-bold text-3xl text-primary block leading-none mb-1">{{ $o->courier_name }}</span>
                            @if($o->tracking_code)<span class="font-mono text-sm text-gray-300 truncate block">{{ $o->tracking_code }}</span>@endif
                        </div>
                        @else
                        <div class="bg-white border-4 border-dark p-6 flex flex-col justify-center border-dashed">
                            <span class="font-sans font-bold text-xs text-gray-500 uppercase tracking-widest block mb-2 border-b-2 border-gray-200 pb-2">কুরিয়ার</span>
                            <span class="font-display font-bold text-2xl text-gray-400 block leading-none mt-2">প্রক্রিয়াধীন</span>
                        </div>
                        @endif
                    </div>

                    <!-- Order Items -->
                    @if($o->items && $o->items->count() > 0)
                    <div class="mb-8">
                        <h5 class="font-display font-bold text-2xl uppercase tracking-widest text-dark mb-4 border-b-4 border-gray-200 pb-2">অর্ডারকৃত পণ্য</h5>
                        <div class="space-y-3">
                            @foreach($o->items as $item)
                            <div class="flex items-center gap-4 bg-white border-2 border-gray-200 p-3">
                                @if($item->product && $item->product->thumbnail)
                                <img src="{{asset('storage/'.$item->product->thumbnail)}}" class="w-14 h-14 object-cover border-2 border-dark" alt="">
                                @endif
                                <div class="flex-1">
                                    <span class="font-display font-bold text-lg text-dark block">{{ $item->product->name ?? $item->product_name ?? 'পণ্য' }}</span>
                                    <span class="text-sm font-sans text-gray-500">পরিমাণ: {{ $item->quantity }} × ৳{{ number_format($item->price) }}</span>
                                </div>
                                <span class="font-display font-bold text-xl text-primary">৳{{ number_format($item->quantity * $item->price) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Delivery Progress Bar (for shipped orders) -->
                    @if($o->order_status == 'shipped')
                    <div class="mt-8 pt-10 border-t-[6px] border-dark relative">
                        <div class="absolute -top-5 left-1/2 transform -translate-x-1/2 bg-white px-6 font-display font-bold text-2xl uppercase tracking-widest text-primary border-4 border-dark -skew-x-[6deg]">
                            <span class="skew-x-[6deg] block">ডেলিভারির অগ্রগতি</span>
                        </div>
                        <div class="flex justify-between text-lg font-display font-bold tracking-widest text-gray-400 mb-4 px-2">
                            <span>উৎপত্তি</span><span class="text-primary">পথে আছে</span><span>গন্তব্য</span>
                        </div>
                        <div class="h-6 bg-gray-200 border-4 border-dark -skew-x-[8deg] relative overflow-hidden">
                            <div class="absolute inset-y-0 left-0 bg-primary w-[75%] animate-pulse"></div>
                        </div>
                    </div>
                    @endif

                    @if($o->order_status == 'completed')
                    <div class="mt-8 p-6 bg-green-50 border-4 border-green-500 -skew-x-[4deg]">
                        <p class="font-display font-bold text-2xl text-green-700 skew-x-[4deg]">
                            <i class="fas fa-check-circle mr-2"></i> আপনার পণ্য সফলভাবে ডেলিভারি হয়েছে। ধন্যবাদ!
                        </p>
                    </div>
                    @endif

                </div>
            </div>
        @empty
            <div class="text-center py-24 bg-gray-50 border-[10px] border-dark border-dashed">
                <div class="w-28 h-28 bg-dark mx-auto flex items-center justify-center text-5xl text-gray-600 border-4 border-gray-600 -skew-x-[8deg] mb-8">
                    <i class="fas fa-search skew-x-[8deg]"></i>
                </div>
                <h3 class="text-4xl md:text-6xl font-display font-black text-dark mb-4 uppercase tracking-tighter mix-blend-multiply">কোনো অর্ডার পাওয়া যায়নি</h3>
                <p class="font-sans font-bold text-lg text-gray-500 max-w-lg mx-auto uppercase tracking-widest border-t-4 border-gray-300 pt-4 mb-10">এই ফোন নম্বরে কোনো অর্ডার নেই। সঠিক নম্বর দিয়ে আবার চেষ্টা করুন।</p>
                
                <a href="{{ $baseUrl }}" class="btn-speed bg-primary px-12 py-6 text-white font-display font-bold text-3xl uppercase tracking-widest border-4 border-dark mx-auto w-fit shadow-dark-lg inline-block">
                    <span>কেনাকাটায় ফিরুন</span>
                </a>
            </div>
        @endforelse
        </div>
    </div>
    @endif
</div>
@endsection
