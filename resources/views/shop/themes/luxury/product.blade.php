@extends('shop.themes.luxury.layout')
@section('title', $product->name . ' | ' . $client->shop_name)
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
<main class="max-w-6xl mx-auto px-4 py-16" x-data="{mainImg:'{{asset('storage/'.$product->thumbnail)}}', color:'', size:''}">
<div class="grid grid-cols-1 md:grid-cols-2 gap-16"><div class="space-y-6"><div class="aspect-[4/5] bg-[#111] border border-[#222]"><img :src="mainImg" class="w-full h-full object-cover opacity-90"></div>
<div class="flex gap-4 overflow-x-auto hide-scroll"><img src="{{asset('storage/'.$product->thumbnail)}}" @click="mainImg=$el.src" class="w-24 h-32 object-cover cursor-pointer border border-[#333] hover:border-primary opacity-60 hover:opacity-100 transition">
@foreach($product->gallery??[] as $img)<img src="{{asset('storage/'.$img)}}" @click="mainImg=$el.src" class="w-24 h-32 object-cover cursor-pointer border border-[#333] hover:border-primary opacity-60 hover:opacity-100 transition">@endforeach</div></div>
<div class="flex flex-col justify-center"><p class="text-xs text-primary tracking-[0.3em] uppercase mb-4">{{$product->category->name??'Luxury'}}</p>
<h1 class="text-3xl md:text-5xl font-heading text-white tracking-widest uppercase mb-6">{{$product->name}}</h1>
<div class="text-2xl text-gray-300 mb-10">৳{{number_format($product->sale_price??$product->regular_price)}} @if($product->sale_price)<del class="text-gray-600 text-lg ml-3">৳{{number_format($product->regular_price)}}</del>@endif</div>
<form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-8 border-y border-[#222] py-8 mb-8">
@if($product->colors)<div><span class="text-[10px] text-gray-500 uppercase tracking-widest block mb-4">Shade / Color</span><div class="flex gap-3">@foreach($product->colors as $c)<label><input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required><span class="px-5 py-2 border border-[#333] text-gray-400 cursor-pointer peer-checked:border-primary peer-checked:text-primary text-xs uppercase tracking-widest transition">{{$c}}</span></label>@endforeach</div></div>@endif
@if($product->sizes)<div><span class="text-[10px] text-gray-500 uppercase tracking-widest block mb-4">Size / Variant</span><div class="flex gap-3">@foreach($product->sizes as $s)<label><input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required><span class="px-5 py-2 border border-[#333] text-gray-400 cursor-pointer peer-checked:border-primary peer-checked:text-primary text-xs uppercase tracking-widest transition">{{$s}}</span></label>@endforeach</div></div>@endif
<button type="submit" class="w-full bg-primary text-black py-4 text-xs font-bold uppercase tracking-[0.2em] hover:bg-white transition">Acquire Now</button></form>
<div class="text-sm text-gray-400 font-light leading-loose">{!!$product->description!!}</div></div></div>
</main>
@endsection