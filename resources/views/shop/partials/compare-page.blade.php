{{--
    Universal Product Compare Page
    ================================
    Compatible with all themes. Each theme can override with its own compare.blade.php
    URL: /compare?ids=1,2,3
--}}
@extends('shop.themes.' . ($client->theme_name ?? 'shwapno') . '.layout')

@section('title', 'পণ্য তুলনা — ' . $client->shop_name)

@section('content')
@php
    $baseUrl = $clean ?? '';
    $currency = $client->currency ?? '৳';
@endphp

<div class="max-w-[1400px] mx-auto px-4 xl:px-8 py-10">
    
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-black text-gray-900">পণ্য তুলনা</h1>
            <p class="text-sm text-gray-500 mt-1">পাশাপাশি তুলনা করে সেরা পণ্যটি বেছে নিন</p>
        </div>
        <a href="javascript:history.back()" class="text-sm text-gray-500 hover:text-gray-800 flex items-center gap-1">
            <i class="fas fa-arrow-left text-xs"></i> পেছনে যান
        </a>
    </div>

    @if($products->isEmpty())
    {{-- Empty state --}}
    <div class="text-center py-24">
        <div class="text-6xl mb-4">⚖️</div>
        <h2 class="text-xl font-bold text-gray-700 mb-2">কোনো পণ্য নির্বাচন করা হয়নি</h2>
        <p class="text-gray-500 mb-6">পণ্যের পেজ থেকে "তুলনা করুন" বাটনে ক্লিক করে পণ্য যোগ করুন।</p>
        <a href="{{ $baseUrl ?: route('shop.show', $client->slug) }}"
            class="inline-flex items-center gap-2 bg-gray-900 text-white font-semibold px-6 py-3 rounded-xl hover:bg-gray-700 transition">
            <i class="fas fa-shopping-bag"></i> কেনাকাটায় ফিরুন
        </a>
    </div>
    @else

    {{-- Compare Table --}}
    <div class="overflow-x-auto rounded-2xl border border-gray-200 shadow-sm">
        <table class="w-full min-w-[640px]">

            {{-- Product Images & Names --}}
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider px-6 py-4 w-40">বৈশিষ্ট্য</th>
                    @foreach($products as $p)
                    <th class="text-center px-4 py-4 border-l border-gray-200">
                        <div class="flex flex-col items-center gap-3">
                            <a href="{{ $clean ? $baseUrl.'/product/'.$p->slug : route('shop.product.details', [$client->slug, $p->slug]) }}">
                                <img src="{{ asset('storage/'.($p->thumbnail ?? $p->image ?? '')) }}"
                                    class="w-24 h-24 object-contain rounded-xl border border-gray-100 hover:shadow-md transition"
                                    onerror="this.src='https://placehold.co/100x100/f9fafb/9ca3af?text=No+Image'"
                                    alt="{{ $p->name }}">
                            </a>
                            <div>
                                <a href="{{ $clean ? $baseUrl.'/product/'.$p->slug : route('shop.product.details', [$client->slug, $p->slug]) }}"
                                    class="font-bold text-sm text-gray-800 hover:text-primary line-clamp-2">{{ $p->name }}</a>
                                <button onclick="removeFromCompare({{ $p->id }}); location.reload();"
                                    class="mt-2 text-xs text-red-400 hover:text-red-600 transition flex items-center gap-1 mx-auto">
                                    <i class="fas fa-times"></i> সরান
                                </button>
                            </div>
                        </div>
                    </th>
                    @endforeach
                    {{-- Empty slots if < 3 products --}}
                    @for($i = $products->count(); $i < 3; $i++)
                    <th class="text-center px-4 py-4 border-l border-gray-200">
                        <div class="flex flex-col items-center gap-3 opacity-30">
                            <div class="w-24 h-24 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-3xl">+</div>
                            <p class="text-xs text-gray-400">পণ্য যোগ করুন</p>
                        </div>
                    </th>
                    @endfor
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">

                {{-- Price --}}
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-600">মূল্য</td>
                    @foreach($products as $p)
                    <td class="text-center px-4 py-4 border-l border-gray-100">
                        @if($p->sale_price && $p->sale_price < $p->regular_price)
                            <span class="text-lg font-black text-red-600">{{ $currency }}{{ number_format((float)$p->sale_price, 0) }}</span>
                            <span class="block text-xs text-gray-400 line-through">{{ $currency }}{{ number_format((float)$p->regular_price, 0) }}</span>
                        @else
                            <span class="text-lg font-black text-gray-800">{{ $currency }}{{ number_format((float)$p->regular_price, 0) }}</span>
                        @endif
                    </td>
                    @endforeach
                    @for($i = $products->count(); $i < 3; $i++)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-gray-300">—</td>
                    @endfor
                </tr>

                {{-- Brand --}}
                @if($products->whereNotNull('brand')->isNotEmpty())
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-600">ব্র্যান্ড</td>
                    @foreach($products as $p)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-sm text-gray-700">{{ $p->brand ?: '—' }}</td>
                    @endforeach
                    @for($i = $products->count(); $i < 3; $i++)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-gray-300">—</td>
                    @endfor
                </tr>
                @endif

                {{-- Category --}}
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-600">ক্যাটাগরি</td>
                    @foreach($products as $p)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-sm text-gray-700">{{ $p->category->name ?? '—' }}</td>
                    @endforeach
                    @for($i = $products->count(); $i < 3; $i++)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-gray-300">—</td>
                    @endfor
                </tr>

                {{-- Stock Status --}}
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-600">স্টক</td>
                    @foreach($products as $p)
                    <td class="text-center px-4 py-4 border-l border-gray-100">
                        @if($p->stock_status === 'out_of_stock' || $p->stock_quantity <= 0)
                            <span class="inline-flex items-center gap-1 text-xs text-red-600 bg-red-50 px-2 py-1 rounded-full font-semibold">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> শেষ
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-semibold">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> পাওয়া যাচ্ছে
                            </span>
                        @endif
                    </td>
                    @endforeach
                    @for($i = $products->count(); $i < 3; $i++)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-gray-300">—</td>
                    @endfor
                </tr>

                {{-- Rating --}}
                @if($products->where('avg_rating', '>', 0)->isNotEmpty())
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-600">রেটিং</td>
                    @foreach($products as $p)
                    <td class="text-center px-4 py-4 border-l border-gray-100">
                        @if($p->avg_rating > 0)
                        <div class="flex items-center justify-center gap-1">
                            <span class="text-yellow-400">★</span>
                            <span class="text-sm font-bold text-gray-800">{{ number_format((float)$p->avg_rating, 1) }}</span>
                            <span class="text-xs text-gray-400">({{ $p->total_reviews ?? 0 }})</span>
                        </div>
                        @else <span class="text-gray-400 text-sm">—</span> @endif
                    </td>
                    @endforeach
                    @for($i = $products->count(); $i < 3; $i++)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-gray-300">—</td>
                    @endfor
                </tr>
                @endif

                {{-- Weight --}}
                @if($products->whereNotNull('weight')->isNotEmpty())
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-600">ওজন</td>
                    @foreach($products as $p)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-sm text-gray-700">{{ $p->weight ? $p->weight.' kg' : '—' }}</td>
                    @endforeach
                    @for($i = $products->count(); $i < 3; $i++)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-gray-300">—</td>
                    @endfor
                </tr>
                @endif

                {{-- Warranty --}}
                @if($products->whereNotNull('warranty')->isNotEmpty())
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-600">ওয়ারেন্টি</td>
                    @foreach($products as $p)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-sm text-gray-700">{{ $p->warranty ?: '—' }}</td>
                    @endforeach
                    @for($i = $products->count(); $i < 3; $i++)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-gray-300">—</td>
                    @endfor
                </tr>
                @endif

                {{-- Key Features (dynamic) --}}
                @foreach($specKeys as $key)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-600">{{ $key }}</td>
                    @foreach($products as $p)
                    @php
                        $features = is_array($p->key_features) ? $p->key_features : json_decode($p->key_features ?? '[]', true);
                        $val = '—';
                        foreach ((array)$features as $f) {
                            if (is_string($f) && $f === $key) { $val = '✓'; break; }
                            if (is_array($f) && ($f['label'] ?? '') === $key) { $val = $f['value'] ?? '✓'; break; }
                        }
                    @endphp
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-sm text-gray-700">
                        @if($val === '✓') <span class="text-green-500 font-bold text-base">✓</span>
                        @elseif($val === '—') <span class="text-gray-300">—</span>
                        @else {{ $val }} @endif
                    </td>
                    @endforeach
                    @for($i = $products->count(); $i < 3; $i++)
                    <td class="text-center px-4 py-4 border-l border-gray-100 text-gray-300">—</td>
                    @endfor
                </tr>
                @endforeach

                {{-- Buy Button Row --}}
                <tr class="bg-gray-50 border-t-2 border-gray-200">
                    <td class="px-6 py-5 text-sm font-bold text-gray-600">অর্ডার করুন</td>
                    @foreach($products as $p)
                    @php
                        $price = $p->sale_price ?? $p->regular_price;
                        $checkoutUrl = $clean
                            ? $baseUrl.'/checkout/'.$p->slug
                            : route('shop.checkout', [$client->slug, $p->slug]);
                    @endphp
                    <td class="text-center px-4 py-5 border-l border-gray-100">
                        @if($p->stock_quantity > 0 || $p->stock_status !== 'out_of_stock')
                        <a href="{{ $checkoutUrl }}"
                            class="inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition w-full">
                            <i class="fas fa-shopping-cart text-xs"></i> কিনুন
                        </a>
                        @else
                        <span class="inline-flex items-center justify-center text-xs text-gray-400 border border-gray-200 px-4 py-2.5 rounded-xl w-full">
                            স্টক নেই
                        </span>
                        @endif
                    </td>
                    @endforeach
                    @for($i = $products->count(); $i < 3; $i++)
                    <td class="text-center px-4 py-5 border-l border-gray-100"></td>
                    @endfor
                </tr>

            </tbody>
        </table>
    </div>

    {{-- Add more products hint --}}
    @if($products->count() < 3)
    <p class="text-center text-sm text-gray-400 mt-6">
        <i class="fas fa-info-circle mr-1"></i>
        পণ্যের পেজ থেকে আরো {{ 3 - $products->count() }}টি পণ্য তুলনা তালিকায় যোগ করতে পারবেন।
    </p>
    @endif

    @endif
</div>

<script>
// Ensure compare functions available on this page too
var COMPARE_KEY = 'compare_{{ $client->id }}';
function getCompareList() { try { return JSON.parse(sessionStorage.getItem(COMPARE_KEY) || '[]'); } catch(e) { return []; } }
function removeFromCompare(id) {
    var list = getCompareList().filter(p => p.id != id);
    sessionStorage.setItem(COMPARE_KEY, JSON.stringify(list));
}
</script>
@endsection
