@extends('shop.themes.grocery.layout')
@section('title', $product->name . ' | ' . $client->shop_name)
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
<main class="max-w-5xl mx-auto px-4 py-8" x-data="{mainImg:'{{asset('storage/'.$product->thumbnail)}}', qty:1, color:'', size:''}">
<div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-8"><div class="space-y-4"><div class="aspect-square bg-gray-50 rounded-2xl overflow-hidden p-6"><img :src="mainImg" class="w-full h-full object-contain mix-blend-multiply"></div>
<div class="flex gap-2 overflow-x-auto hide-scroll"><img src="{{asset('storage/'.$product->thumbnail)}}" @click="mainImg=$el.src" class="w-16 h-16 object-cover rounded-xl border-2 cursor-pointer hover:border-primary">
@foreach($product->gallery??[] as $img)<img src="{{asset('storage/'.$img)}}" @click="mainImg=$el.src" class="w-16 h-16 object-cover rounded-xl border-2 cursor-pointer hover:border-primary">@endforeach</div></div>
<div class="flex flex-col"><span class="bg-primary/10 text-primary px-3 py-1 text-xs font-bold rounded-full w-fit mb-3">{{$product->category->name??'Grocery'}}</span>
<h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 mb-2">{{$product->name}}</h1>
<div class="flex items-end gap-2 mb-6"><span class="text-3xl font-extrabold text-primary">৳{{number_format($product->sale_price??$product->regular_price)}}</span>@if($product->sale_price)<del class="text-gray-400 font-bold mb-1">৳{{number_format($product->regular_price)}}</del>@endif</div>
<form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-6">
@if($product->colors)<div class="mb-4"><span class="text-sm font-bold block mb-2">Options:</span><div class="flex gap-2">@foreach($product->colors as $c)<label><input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required><span class="px-4 py-2 border-2 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 text-sm font-bold">{{$c}}</span></label>@endforeach</div></div>@endif
@if($product->sizes)<div class="mb-4"><span class="text-sm font-bold block mb-2">Weight/Size:</span><div class="flex gap-2">@foreach($product->sizes as $s)<label><input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required><span class="px-4 py-2 border-2 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 text-sm font-bold">{{$s}}</span></label>@endforeach</div></div>@endif
<div class="flex gap-4 items-center"><div class="flex items-center bg-gray-100 rounded-xl p-1"><button type="button" @click="if(qty>1)qty--" class="w-10 h-10 flex items-center justify-center font-bold text-lg rounded-lg hover:bg-white">-</button><input type="number" name="qty" x-model="qty" class="w-12 text-center bg-transparent border-none font-bold text-lg p-0 focus:ring-0" readonly><button type="button" @click="qty++" class="w-10 h-10 flex items-center justify-center font-bold text-lg rounded-lg hover:bg-white">+</button></div>
<button type="submit" class="flex-1 bg-primary text-white py-3.5 rounded-xl font-extrabold text-lg flex items-center justify-center gap-2 hover:bg-green-600 transition shadow-lg shadow-green-500/30"><i class="fas fa-shopping-basket"></i> Buy Now</button></div></form>
<div class="mt-8 text-sm text-gray-600 border-t pt-6">{!! clean($product->description) !!}</div></div></div>
</main>
@endsection