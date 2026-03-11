@extends('shop.themes.fashion.layout')
@section('title', $product->name . ' | ' . $client->shop_name)
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
<main class="max-w-6xl mx-auto px-4 py-12" x-data="{mainImg:'{{asset('storage/'.$product->thumbnail)}}', color:'', size:''}">
<div class="grid grid-cols-1 md:grid-cols-2 gap-12"><div class="space-y-4"><div class="aspect-[3/4] bg-gray-50 overflow-hidden"><img :src="mainImg" class="w-full h-full object-cover"></div>
<div class="flex gap-2 overflow-x-auto hide-scroll"><img src="{{asset('storage/'.$product->thumbnail)}}" @click="mainImg=$el.src" class="w-20 h-24 object-cover cursor-pointer border hover:border-black">
@foreach($product->gallery??[] as $img)<img src="{{asset('storage/'.$img)}}" @click="mainImg=$el.src" class="w-20 h-24 object-cover cursor-pointer border hover:border-black">@endforeach</div></div>
<div class="flex flex-col justify-center"><p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 mb-2">{{$product->category->name??'Collection'}}</p>
<h1 class="text-3xl md:text-4xl font-heading mb-4">{{$product->name}}</h1>
<div class="text-2xl mb-6 font-medium">৳{{number_format($product->sale_price??$product->regular_price)}} @if($product->sale_price)<del class="text-gray-400 text-lg ml-2">৳{{number_format($product->regular_price)}}</del>@endif</div>
<form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-6">
@if($product->colors)<div class="mb-4"><span class="text-xs uppercase tracking-widest block mb-2">Color</span><div class="flex gap-2">@foreach($product->colors as $c)<label><input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required><span class="px-4 py-2 border cursor-pointer peer-checked:bg-black peer-checked:text-white text-xs uppercase">{{$c}}</span></label>@endforeach</div></div>@endif
@if($product->sizes)<div class="mb-6"><span class="text-xs uppercase tracking-widest block mb-2">Size</span><div class="flex gap-2">@foreach($product->sizes as $s)<label><input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required><span class="px-4 py-2 border cursor-pointer peer-checked:bg-black peer-checked:text-white text-xs uppercase">{{$s}}</span></label>@endforeach</div></div>@endif
<button type="submit" class="w-full bg-black text-white py-4 text-xs uppercase tracking-[0.2em] hover:bg-gray-800 transition">Proceed to Checkout</button></form>
<div class="mt-10 text-sm text-gray-600 leading-relaxed border-t pt-8">{!!$product->description!!}</div></div></div>
</main>
@endsection