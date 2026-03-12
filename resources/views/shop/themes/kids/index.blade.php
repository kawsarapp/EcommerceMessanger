@extends('shop.themes.kids.layout')
@section('title', $client->shop_name . ' | Fun Kids Store')
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
@if($client->banner)<div class="w-full h-48 md:h-72 bg-cover bg-center" style="background-image:url('{{asset('storage/'.$client->banner)}}');"><div class="w-full h-full bg-black/20 flex items-center justify-center"><h2 class="text-4xl md:text-6xl text-white font-heading font-bold drop-shadow-lg text-center px-4">{{$client->meta_title??'Kids Wonderland!'}}</h2></div></div>@endif
<div class="max-w-7xl mx-auto px-4 py-10"><div class="flex justify-between items-center mb-8"><h3 class="font-heading text-3xl text-gray-800">Playful Picks 🎨</h3></div>
<div class="flex gap-3 overflow-x-auto hide-scroll pb-4">
<a href="?category=all" class="px-6 py-3 rounded-full font-bold whitespace-nowrap {{!request('category')||request('category')=='all'?'bg-primary text-white fun-shadow':'bg-white text-gray-600 border-2 border-gray-100'}}">All Toys</a>
@foreach($categories as $c)<a href="?category={{$c->slug}}" class="px-6 py-3 rounded-full font-bold whitespace-nowrap {{request('category')==$c->slug?'bg-primary text-white fun-shadow':'bg-white text-gray-600 border-2 border-gray-100 hover:border-primary/50'}}">{{$c->name}}</a>@endforeach
</div>
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6 mt-4">
@foreach($products as $p) <div class="bg-white p-3 rounded-3xl border-2 border-gray-100 hover:border-primary/50 hover:-translate-y-2 transition-transform flex flex-col relative group fun-shadow">
@if($p->sale_price)<span class="absolute top-2 left-2 z-10 bg-yellow-400 text-gray-900 text-xs font-heading font-bold px-3 py-1 rounded-full shadow-sm">-{{round((($p->regular_price-$p->sale_price)/$p->regular_price)*100)}}%</span>@endif
<a href="{{$baseUrl.'/product/'.$p->slug}}" class="block aspect-square bg-[#F8FAFC] rounded-2xl overflow-hidden mb-4"><img src="{{asset('storage/'.$p->thumbnail)}}" class="w-full h-full object-contain mix-blend-multiply p-2 group-hover:scale-110 transition"></a>
<a href="{{$baseUrl.'/product/'.$p->slug}}" class="font-bold text-gray-800 text-sm md:text-base leading-tight hover:text-primary mb-3 flex-1">{{$p->name}}</a>
<div class="flex items-center justify-between mt-auto"><div class="flex flex-col"><span class="font-heading font-bold text-xl text-primary leading-none">৳{{number_format($p->sale_price??$p->regular_price)}}</span>@if($p->sale_price)<del class="text-xs text-gray-400 font-bold">৳{{$p->regular_price}}</del>@endif</div>
<a href="{{$baseUrl.'/checkout/'.$p->slug}}" class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center hover:bg-primaryDark transition shadow-md"><i class="fas fa-gift"></i></a></div></div> @endforeach
</div><div class="mt-10">{{$products->links('pagination::tailwind')}}</div></div>
@endsection