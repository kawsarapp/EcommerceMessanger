@extends('shop.themes.modern.layout')
@section('title', $client->shop_name . ' | Modern Essentials')
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
@if($client->banner)<div class="w-full h-[50vh] md:h-[60vh] bg-cover bg-center" style="background-image:url('{{asset('storage/'.$client->banner)}}');"><div class="w-full h-full bg-black/30 flex items-end p-8 md:p-16"><h2 class="text-4xl md:text-7xl text-white font-extrabold tracking-tighter max-w-2xl">{{$client->meta_title??'Discover The New Era'}}</h2></div></div>@endif
<div class="max-w-7xl mx-auto px-6 py-16"><div class="flex flex-col md:flex-row justify-between md:items-end mb-12 gap-6"><h3 class="text-3xl md:text-4xl font-extrabold tracking-tight">Latest Arrivals</h3>
<div class="flex gap-4 overflow-x-auto hide-scroll pb-2"><a href="?category=all" class="text-sm font-semibold uppercase tracking-widest {{!request('category')||request('category')=='all'?'border-b-2 border-black':'text-gray-400 hover:text-black'}} pb-1">All</a>@foreach($categories as $c)<a href="?category={{$c->slug}}" class="text-sm font-semibold uppercase tracking-widest {{request('category')==$c->slug?'border-b-2 border-black':'text-gray-400 hover:text-black'}} pb-1">{{$c->name}}</a>@endforeach</div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-12">
@foreach($products as $p) <a href="{{$baseUrl.'/product/'.$p->slug}}" class="group flex flex-col">
<div class="aspect-[4/5] bg-gray-100 mb-5 relative overflow-hidden">@if($p->sale_price)<span class="absolute top-4 left-4 z-10 bg-black text-white text-xs font-bold px-3 py-1 uppercase tracking-widest">Sale</span>@endif<img src="{{asset('storage/'.$p->thumbnail)}}" class="w-full h-full object-cover mix-blend-multiply group-hover:scale-105 transition duration-700"></div>
<div class="flex justify-between items-start"><div class="pr-4"><h4 class="font-semibold text-lg leading-snug group-hover:text-gray-500 transition">{{$p->name}}</h4><p class="text-xs text-gray-400 mt-2 uppercase tracking-widest">{{$p->category->name??'Modern'}}</p></div>
<div class="text-right"><span class="font-bold text-lg block">৳{{number_format($p->sale_price??$p->regular_price)}}</span>@if($p->sale_price)<del class="text-xs text-gray-400 font-medium">৳{{$p->regular_price}}</del>@endif</div></div></a> @endforeach
</div><div class="mt-16">{{$products->links('pagination::tailwind')}}</div></div>
@endsection