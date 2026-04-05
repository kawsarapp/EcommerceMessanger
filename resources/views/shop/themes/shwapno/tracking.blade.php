@extends('shop.themes.shwapno.layout')
@section('title', 'Track Order | ' . $client->shop_name)

@section('content')
<div class="max-w-[800px] mx-auto px-4 py-10 lg:py-16">

    {{-- ✅ ORDER CONFIRMED SUCCESS BANNER --}}
    @if(session('order_confirmed'))
    <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-8 sm:p-10 mb-8 text-white shadow-xl">
        {{-- Decorative circles --}}
        <div class="absolute -right-10 -top-10 w-48 h-48 bg-white opacity-10 rounded-full"></div>
        <div class="absolute -left-6 -bottom-6 w-32 h-32 bg-white opacity-10 rounded-full"></div>

        <div class="relative z-10 text-center">
            {{-- Checkmark icon --}}
            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-5 border-4 border-white border-opacity-30">
                <i class="fas fa-check text-4xl text-white"></i>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black mb-2">🎉 অর্ডার সম্পন্ন হয়েছে!</h1>
            <p class="text-green-100 text-sm mb-6">আপনার অর্ডার সফলভাবে রেকর্ড করা হয়েছে। আমরা শীঘ্রই যোগাযোগ করব।</p>

            {{-- Order ID big display --}}
            <div class="inline-block bg-white bg-opacity-20 border border-white border-opacity-30 rounded-xl px-8 py-4 mb-6">
                <div class="text-green-100 text-[11px] font-bold uppercase tracking-widest mb-1">আপনার অর্ডার নম্বর</div>
                <div class="text-4xl sm:text-5xl font-black tracking-wider">#{{ session('order_id') ?? request('order_id') }}</div>
            </div>

            {{-- Instructions --}}
            <div class="bg-white bg-opacity-15 border border-white border-opacity-20 rounded-xl p-4 mb-6 text-left max-w-sm mx-auto">
                <p class="text-[12px] font-bold uppercase tracking-wider text-green-100 mb-3 text-center">এই নম্বরটি সেভ করুন</p>
                <div class="space-y-2 text-sm">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-circle-check text-green-200 mt-0.5 text-xs shrink-0"></i>
                        <span>এই অর্ডার ID দিয়ে যেকোনো সময় অর্ডারের অবস্থান জানতে পারবেন</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="fas fa-circle-check text-green-200 mt-0.5 text-xs shrink-0"></i>
                        <span>নিচের Track বক্সে Order ID লিখে সার্চ করুন</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="fas fa-circle-check text-green-200 mt-0.5 text-xs shrink-0"></i>
                        <span>আমাদের টিম আপনার দেওয়া নম্বরে যোগাযোগ করবে</span>
                    </div>
                </div>
            </div>

            <p class="text-green-100 text-[11px] opacity-80">
                <i class="fas fa-lock mr-1"></i> আপনার তথ্য সম্পূর্ণ সুরক্ষিত
            </p>
        </div>
    </div>
    @endif

    {{-- SEARCH FORM --}}
    <div class="bg-white border border-gray-100 shadow-sm rounded-lg p-8 sm:p-10 mb-8 relative overflow-hidden">
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-primary/5 rounded-full opacity-50 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-yellow-50 rounded-full opacity-50 blur-2xl pointer-events-none"></div>
        
        <div class="relative z-10 text-center mb-8">
            <h2 class="text-[22px] font-black text-gray-800 mb-2 tracking-tight">Track Your Order</h2>
            <p class="text-sm text-gray-500 max-w-md mx-auto">আপনার <strong>Order ID</strong> লিখুন — অর্ডারের সর্বশেষ অবস্থা দেখতে পাবেন।</p>
        </div>

        <div class="max-w-md mx-auto relative z-10">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-hashtag"></i>
                    </div>
                    <input type="number" name="order_id" value="{{ request('order_id') }}" placeholder="Order ID লিখুন (যেমন: 1042)" required min="1"
                        class="w-full pl-10 pr-4 py-3.5 border border-gray-200 rounded-full text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-red-100 transition shadow-inner">
                </div>
                <button type="submit" class="bg-primary hover:bg-[#c8161c] text-white px-8 py-3.5 rounded-full font-bold text-sm transition shadow-md whitespace-nowrap">
                    <i class="fas fa-search mr-1.5 opacity-80"></i> Track
                </button>
            </form>
            <p class="text-center text-[11px] text-gray-400 mt-3">
                <i class="fas fa-lock mr-1"></i> আপনার ফোন নম্বর প্রয়োজন নেই — শুধু Order ID দিন।
            </p>
        </div>
    </div>

    {{-- ORDER RESULT --}}
    @if(request('order_id'))
    <div class="animate-fade-in">
        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-6">
            Order <span class="text-primary">#{{ request('order_id') }}</span> এর তথ্য
        </h4>

        @forelse($orders ?? [] as $o)
            <div class="bg-white border border-gray-200 rounded-xl p-6 sm:p-8 mb-6 shadow-sm hover:shadow-md transition">
                
                {{-- Header: Order ID + Status --}}
                <div class="flex flex-wrap justify-between items-start gap-4 mb-6 pb-5 border-b border-dashed border-gray-200">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Order Number</span>
                        <span class="text-3xl font-black text-gray-800">#{{ $o->id }}</span>
                        <span class="text-[11px] text-gray-400 block mt-1">{{ $o->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    
                    @php
                        $statusColors = [
                            'pending'    => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                            'processing' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'shipped'    => 'bg-purple-50 text-purple-700 border-purple-200',
                            'completed'  => 'bg-green-50 text-green-700 border-green-200',
                            'cancelled'  => 'bg-primary/5 text-red-700 border-red-200',
                        ];
                        $statusIcons = [
                            'pending'    => 'fa-clock',
                            'processing' => 'fa-cog fa-spin',
                            'shipped'    => 'fa-truck',
                            'completed'  => 'fa-check-circle',
                            'cancelled'  => 'fa-times-circle',
                        ];
                        $statusLabels = [
                            'pending'    => 'অপেক্ষমান',
                            'processing' => 'প্রক্রিয়াধীন',
                            'shipped'    => 'পাঠানো হয়েছে',
                            'completed'  => 'সম্পন্ন',
                            'cancelled'  => 'বাতিল',
                        ];
                        $badgeClass = $statusColors[$o->order_status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                        $icon = $statusIcons[$o->order_status] ?? 'fa-circle';
                        $label = $statusLabels[$o->order_status] ?? ucfirst($o->order_status);
                    @endphp
                    
                    <span class="text-[11px] font-bold px-4 py-2 rounded-full uppercase tracking-wider border {{ $badgeClass }} flex items-center shadow-sm gap-2">
                        <i class="fas {{ $icon }} text-[10px]"></i>
                        {{ $label }}
                    </span>
                </div>

                {{-- Details Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-5 mb-6">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-[9px] font-bold text-gray-400 block mb-1 uppercase tracking-wider">তারিখ</span>
                        <span class="text-[13px] font-bold text-gray-700">{{ $o->created_at->format('d M, Y') }}</span>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-[9px] font-bold text-gray-400 block mb-1 uppercase tracking-wider">মোট পরিমাণ</span>
                        <span class="text-[16px] font-black text-primary">৳{{ number_format($o->total_amount, 0) }}</span>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-[9px] font-bold text-gray-400 block mb-1 uppercase tracking-wider">পেমেন্ট</span>
                        <span class="text-[12px] font-bold {{ $o->payment_status=='paid' ? 'text-green-600' : 'text-orange-500' }} uppercase flex items-center gap-1">
                            <i class="fas {{ $o->payment_status=='paid' ? 'fa-check-circle' : 'fa-clock' }} text-[10px]"></i>
                            {{ $o->payment_status === 'paid' ? 'পরিশোধিত' : 'বাকি' }}
                        </span>
                    </div>
                    @if($o->courier_name)
                    <div class="bg-blue-50 rounded-lg p-3">
                        <span class="text-[9px] font-bold text-blue-400 block mb-1 uppercase tracking-wider">কুরিয়ার</span>
                        <span class="text-[12px] font-bold text-gray-800">{{ $o->courier_name }}</span>
                        @if($o->tracking_code)
                            <span class="text-[10px] text-gray-500 font-mono mt-1 block bg-white px-2 py-0.5 rounded border border-blue-100">{{ $o->tracking_code }}</span>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Progress Bar --}}
                @php
                    $steps = ['pending' => 1, 'processing' => 2, 'shipped' => 3, 'completed' => 4];
                    $currentStep = $steps[$o->order_status] ?? 1;
                @endphp
                @if($o->order_status !== 'cancelled')
                <div class="border border-gray-100 rounded-xl p-4 bg-gray-50">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4 text-center">Delivery Progress</p>
                    <div class="flex items-center justify-between relative">
                        {{-- Progress line --}}
                        <div class="absolute top-4 left-0 right-0 h-1 bg-gray-200 z-0">
                            <div class="h-full bg-primary transition-all duration-700" style="width: {{ ($currentStep - 1) * 33 }}%"></div>
                        </div>
                        @foreach([['icon'=>'fa-clipboard-check','label'=>'অর্ডার গৃহীত'], ['icon'=>'fa-cog','label'=>'প্রক্রিয়াকরণ'], ['icon'=>'fa-truck','label'=>'ডেলিভারিতে'], ['icon'=>'fa-house','label'=>'পৌঁছানো']] as $i => $step)
                        <div class="relative z-10 flex flex-col items-center gap-1.5" style="width:25%">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ ($i+1) <= $currentStep ? 'bg-primary text-white' : 'bg-white text-gray-300 border border-gray-200' }} shadow-sm transition-all duration-500">
                                <i class="fas {{ $step['icon'] }} text-[11px]"></i>
                            </div>
                            <span class="text-[9px] font-bold text-center leading-tight {{ ($i+1) <= $currentStep ? 'text-gray-700' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        @empty
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-10 text-center">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm border">
                    <i class="fas fa-search text-gray-300 text-xl"></i>
                </div>
                <h3 class="text-[15px] font-bold text-gray-700 mb-1">অর্ডার পাওয়া যায়নি</h3>
                <p class="text-[12px] text-gray-500 max-w-xs mx-auto">Order ID <span class="font-bold">#{{ request('order_id') }}</span> এর কোনো অর্ডার নেই। সঠিক ID দিন।</p>
            </div>
        @endforelse
    </div>
    @endif

</div>

@php $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); @endphp
@endsection


