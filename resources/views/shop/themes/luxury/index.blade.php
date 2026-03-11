@extends('shop.themes.luxury.layout')
@section('title', $client->shop_name . ' | Exclusive Luxury')
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
@if($client->banner)<div class="w-full h-[60vh] bg-cover bg-center relative flex items-center justify-center" style="background-image:url('{{asset('storage/'.$client->banner)}}');"><div class="absolute inset-0 bg-black/60"></div><div class="relative text-center border border-primary/50 p-8 bg-black/30 backdrop-blur-sm"><h2 class="text-4xl md:text-5xl text-primary font-heading tracking-[0.2em] uppercase mb-2">{{$client->meta_title??'Prestige'}}</h2><p class="text-xs tracking-[0.3em] uppercase text-gray-300">New Arrivals</p></div></div>@endif
<div class="max-w-7xl mx-auto px-4 py-20"><div class="text-center mb-16"><h3 class="font-heading text-3xl text-primary tracking-widest uppercase mb-4">The Collection</h3><div class="w-16 h-0.5 bg-primary mx-auto"></div></div>
<div class="flex gap-6 justify-center overflow-x-auto hide-scroll pb-4 mb-10">
<a href="?category=all" class="text-xs tracking-[0.2em] uppercase {{!request('category')||request('category')=='all'?'text-primary border-b border-primary':'text-gray-500 hover:text-gray-300'}} pb-1">All Pieces</a>
@foreach($categories as $c)<a href="?category={{$c->slug}}" class="text-xs tracking-[0.2em] uppercase {{request('category')==$c->slug?'text-primary border-b border-primary':'text-gray-500 hover:text-gray-300'}} pb-1">{{$c->name}}</a>@endforeach
</div>
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8">
@foreach($products as $p) <a href="{{$baseUrl.'/product/'.$p->slug}}" class="group block"><div class="aspect-[4/5] bg-[#111] overflow-hidden relative border border-[#222] group-hover:border-primary/50 transition duration-500">
<img src="{{asset('storage/'.$p->thumbnail)}}" class="w-full h-full object-cover transition duration-1000 group-hover:scale-110 opacity-80 group-hover:opacity-100"></div>
<div class="pt-6 text-center"><h4 class="font-heading text-lg text-gray-200 group-hover:text-primary transition tracking-widest uppercase mb-2">{{$p->name}}</h4>
<p class="text-sm text-primary">৳{{number_format($p->sale_price??$p->regular_price)}} @if($p->sale_price)<del class="text-gray-600 text-xs ml-2">৳{{$p->regular_price}}</del>@endif</p></div></a> @endforeach
</div><div class="mt-16">{{$products->links('pagination::tailwind')}}</div></div>
@endsection