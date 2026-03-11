@extends('shop.themes.fashion.layout')
@section('title', $client->shop_name . ' | Exquisite Fashion')
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
<div class="w-full h-[50vh] md:h-[70vh] bg-cover bg-center relative flex items-center justify-center" style="background-image:url('{{asset('storage/'.($client->banner??''))}}');">
<div class="absolute inset-0 bg-black/30"></div><h2 class="relative text-3xl md:text-6xl text-white font-heading text-center tracking-widest uppercase px-4">{{$client->meta_title??'New Collection'}}</h2></div>
<div class="max-w-7xl mx-auto px-4 py-16"><div class="flex justify-between items-end mb-10"><h3 class="font-heading text-3xl italic">Trending Now</h3>
<form action="" method="GET"><select name="category" onchange="this.form.submit()" class="text-xs border-b border-gray-300 bg-transparent py-1 uppercase tracking-widest outline-none"><option value="all">All Styles</option>@foreach($categories as $c)<option value="{{$c->slug}}" {{request('category')==$c->slug?'selected':''}}>{{$c->name}}</option>@endforeach</select></form></div>
<div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-10">
@foreach($products as $p) <a href="{{$baseUrl.'/product/'.$p->slug}}" class="group"><div class="aspect-[3/4] bg-gray-100 overflow-hidden relative">
<img src="{{asset('storage/'.$p->thumbnail)}}" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
@if($p->sale_price)<span class="absolute top-3 left-3 bg-red-600 text-white text-[9px] px-2 py-1 uppercase tracking-widest">Sale</span>@endif</div>
<div class="pt-4 text-center"><p class="text-[10px] text-gray-400 uppercase tracking-[0.2em]">{{$p->category->name??'Fashion'}}</p>
<h4 class="font-heading text-lg mt-1 group-hover:text-primary transition truncate">{{$p->name}}</h4>
<p class="mt-2 text-sm text-gray-900 font-medium">৳{{number_format($p->sale_price??$p->regular_price)}} @if($p->sale_price)<del class="text-gray-400 ml-1 text-xs">৳{{$p->regular_price}}</del>@endif</p></div></a> @endforeach
</div><div class="mt-12">{{$products->links('pagination::tailwind')}}</div></div>
@endsection