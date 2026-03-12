@extends('shop.themes.modern.layout')
@section('title', 'Track Order | ' . $client->shop_name)
@section('content')
<main class="max-w-3xl mx-auto px-6 py-20 md:py-32">
<div class="text-center mb-16"><h1 class="text-4xl md:text-6xl font-extrabold tracking-tighter mb-4">Track.</h1><p class="text-gray-500 font-medium">Enter your phone number below.</p></div>
<form action="{{route('shop.track.submit', $client->slug)}}" method="POST" class="flex max-w-md mx-auto border-b-2 border-gray-200 focus-within:border-black transition">@csrf
<input type="tel" name="phone" value="{{$phone??''}}" required class="flex-1 bg-transparent py-4 outline-none font-bold text-xl text-center placeholder-gray-300" placeholder="01XXXXXXXXX">
<button type="submit" class="px-6 font-bold uppercase tracking-widest text-sm hover:text-gray-500 transition">Go</button></form>
@if(isset($phone)) <div class="mt-20 space-y-8">
@forelse($orders??[] as $o)
<div class="border border-gray-200 p-8 md:p-10"><div class="flex justify-between items-start mb-8 pb-8 border-b border-gray-100">
<div><span class="text-xs font-bold text-gray-400 uppercase tracking-widest block mb-1">Order Number</span><span class="font-extrabold text-2xl tracking-tighter">#{{$o->id}}</span></div>
<div class="text-right"><span class="text-xs font-bold text-gray-400 uppercase tracking-widest block mb-1">Status</span><span class="font-bold text-sm uppercase px-3 py-1 bg-gray-100">{{$o->order_status}}</span></div></div>
<div class="space-y-6">@foreach($o->items as $i)<div class="flex gap-6 items-center"><div class="w-16 h-20 bg-gray-50 border border-gray-100"><img src="{{asset('storage/'.$i->product->thumbnail)}}" class="w-full h-full object-cover mix-blend-multiply"></div><div class="flex-1"><p class="font-bold text-base">{{$i->product->name}}</p><p class="text-xs font-semibold text-gray-500 mt-2">QTY: {{$i->quantity}}</p></div><span class="font-bold text-lg">৳{{$i->price}}</span></div>@endforeach</div>
<div class="flex justify-between items-center mt-10 pt-8 border-t border-gray-100"><span class="font-bold uppercase tracking-widest text-sm">Total Paid</span><span class="text-3xl font-extrabold tracking-tighter">৳{{$o->total_amount}}</span></div></div>
@empty <p class="text-gray-400 text-center font-medium">No active orders found.</p> @endforelse
</div>@endif </main>
@endsection