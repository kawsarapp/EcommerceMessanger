@extends('shop.themes.luxury.layout')
@section('title', 'Track Order | ' . $client->shop_name)
@section('content')
<main class="max-w-3xl mx-auto px-4 py-20 text-center">
<h1 class="text-3xl font-heading text-primary tracking-[0.2em] uppercase mb-4">Order Status</h1><p class="text-[10px] text-gray-500 uppercase tracking-widest mb-12">Enter mobile number</p>
<form action="{{route('shop.track.submit', $client->slug)}}" method="POST" class="max-w-md mx-auto flex border-b border-[#444] focus-within:border-primary transition">@csrf
<input type="tel" name="phone" value="{{$phone??''}}" required class="w-full py-3 bg-transparent outline-none text-center tracking-widest text-white" placeholder="01XXXXXXXXX">
<button type="submit" class="px-6 text-[10px] text-primary uppercase font-bold tracking-[0.2em] hover:text-white transition">Track</button></form>
@if(isset($phone)) <div class="mt-20 text-left space-y-8">
@forelse($orders??[] as $o)
<div class="border border-[#222] p-8 bg-[#0a0a0a]"><div class="flex justify-between items-center border-b border-[#222] pb-6 mb-6">
<span class="font-heading text-xl text-white tracking-widest">#{{$o->id}}</span><span class="text-[10px] text-primary uppercase tracking-[0.2em] border border-primary/30 px-3 py-1">{{$o->order_status}}</span></div>
@foreach($o->items as $i)<div class="flex gap-6 mb-6"><img src="{{asset('storage/'.$i->product->thumbnail)}}" class="w-16 h-20 object-cover border border-[#222]"><div class="flex-1"><p class="font-heading text-gray-300 tracking-widest uppercase mb-2">{{$i->product->name}}</p><p class="text-[10px] text-gray-500 uppercase tracking-widest">Qty: {{$i->quantity}} | ৳{{$i->price}}</p></div></div>@endforeach
<div class="text-right text-lg font-heading text-primary tracking-widest mt-6 pt-6 border-t border-[#222]">Total: ৳{{$o->total_amount}}</div></div>
@empty <p class="text-gray-500 text-sm tracking-widest uppercase text-center">No records found.</p> @endforelse
</div>@endif </main>
@endsection