@extends('shop.themes.grocery.layout')
@section('title', $client->shop_name . ' | Fresh Groceries')
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
@if($client->banner)<div class="w-full h-40 md:h-64 bg-cover bg-center rounded-b-3xl" style="background-image:url('{{asset('storage/'.$client->banner)}}');"><div class="w-full h-full bg-black/40 rounded-b-3xl flex items-center justify-center"><h2 class="text-3xl md:text-5xl text-white font-extrabold px-4 text-center">{{$client->meta_title??'Daily Fresh Needs'}}</h2></div></div>@endif
<div class="max-w-7xl mx-auto px-4 py-8"><div class="flex justify-between items-center mb-6"><h3 class="font-extrabold text-2xl text-gray-800">Categories</h3></div>
<div class="flex gap-3 overflow-x-auto hide-scroll pb-4">
<a href="?category=all" class="px-5 py-2 rounded-full font-bold whitespace-nowrap {{!request('category')||request('category')=='all'?'bg-primary text-white':'bg-white text-gray-600 border'}}">All Items</a>
@foreach($categories as $c)<a href="?category={{$c->slug}}" class="px-5 py-2 rounded-full font-bold whitespace-nowrap {{request('category')==$c->slug?'bg-primary text-white':'bg-white text-gray-600 border hover:bg-green-50'}}">{{$c->name}}</a>@endforeach
</div>
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mt-4">
@foreach($products as $p) <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 hover:border-primary transition flex flex-col relative group">
@if($p->sale_price)<span class="absolute top-2 left-2 z-10 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-md">-{{round((($p->regular_price-$p->sale_price)/$p->regular_price)*100)}}%</span>@endif
<a href="{{$baseUrl.'/product/'.$p->slug}}" class="block aspect-square bg-gray-50 rounded-xl overflow-hidden mb-3"><img src="{{asset('storage/'.$p->thumbnail)}}" class="w-full h-full object-contain mix-blend-multiply group-hover:scale-110 transition"></a>
<a href="{{$baseUrl.'/product/'.$p->slug}}" class="font-bold text-gray-800 text-sm leading-tight line-clamp-2 hover:text-primary mb-2 flex-1">{{$p->name}}</a>
<div class="flex items-center justify-between mt-auto"><div class="flex flex-col"><span class="font-extrabold text-lg text-primary leading-none">৳{{number_format($p->sale_price??$p->regular_price)}}</span>@if($p->sale_price)<del class="text-[10px] text-gray-400 font-bold">৳{{$p->regular_price}}</del>@endif</div>
<a href="{{$baseUrl.'/checkout/'.$p->slug}}" class="w-8 h-8 bg-primary/10 text-primary rounded-full flex items-center justify-center hover:bg-primary hover:text-white transition"><i class="fas fa-plus"></i></a></div></div> @endforeach
</div><div class="mt-8">{{$products->links('pagination::tailwind')}}</div></div>
@endsection